<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Plan;
use App\Repositories\ContributionScheduleRepository;
use App\Repositories\CreditScoreRepository;
use App\Repositories\LedgerRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PlanMemberRepository;
use App\Repositories\PlanRepository;
use DateInterval;
use DateTimeImmutable;
use PDO;
use RuntimeException;

/**
 * Business logic for savings plans (Main Kutu circles).
 *
 * Handles plan creation, status changes, schedule generation and dashboard
 * statistics. Audit events are recorded through AuditService.
 */
final class PlanService
{
    private const FREQUENCY_INTERVALS = [
        'weekly'     => 'P7D',
        'biweekly'   => 'P14D',
        'monthly'    => 'P1M',
        'quarterly'  => 'P3M',
    ];

    public function __construct(
        private PlanRepository $plans,
        private PlanMemberRepository $planMembers,
        private ContributionScheduleRepository $schedules,
        private CreditScoreRepository $creditScores,
        private NotificationRepository $notifications,
        private AuthService $auth,
        private LedgerRepository $ledger,
    ) {}

    /**
     * Create a new plan.
     *
     * @return array{ok: bool, id?: int, error?: string}
     */
    public function createPlan(array $data, ?int $createdBy): array
    {
        foreach (['name', 'contribution_amount', 'number_of_cycles', 'start_date'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                return ['ok' => false, 'error' => 'Ruangan wajib tidak diisi: ' . $field];
            }
        }

        if (empty($data['plan_code'])) {
            $data['plan_code'] = 'PLN-' . strtoupper(substr(md5(uniqid()), 0, 6));
        }

        $data['created_by'] = $createdBy;
        $data['status'] = $data['status'] ?? 'draft';

        $id = $this->plans->create($data);

        AuditService::log('plan.create', $createdBy, 'plan', $id, [
            'plan_code' => $data['plan_code'],
            'name'      => $data['name'],
        ]);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Update an existing plan.
     *
     * @return array{ok: bool, error?: string}
     */
    public function updatePlan(int $planId, array $data): array
    {
        $plan = $this->plans->findById($planId);
        if ($plan === null) {
            return ['ok' => false, 'error' => 'Pelan tidak dijumpai.'];
        }

        $this->plans->update($planId, $data);

        AuditService::log('plan.update', $this->auth->currentUser()?->id, 'plan', $planId, $data);

        return ['ok' => true];
    }

    /**
     * List plans, optionally filtered by status/search.
     *
     * @return Plan[]
     */
    public function list(array $filters): array
    {
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;
        return $this->plans->all($status, $search);
    }

    public function find(int $planId): ?Plan
    {
        return $this->plans->findById($planId);
    }

    /**
     * Generate contribution schedules for every active member of a plan.
     *
     * One row per cycle (1 .. number_of_cycles). Due dates are computed from
     * the plan start date using the payment frequency interval.
     *
     * @return array{ok: bool, count?: int, error?: string}
     */
    public function generateSchedules(int $planId): array
    {
        $plan = $this->plans->findById($planId);
        if ($plan === null) {
            return ['ok' => false, 'error' => 'Pelan tidak dijumpai.'];
        }

        $intervalSpec = self::FREQUENCY_INTERVALS[$plan->paymentFrequency] ?? 'P1M';
        $interval = new DateInterval($intervalSpec);

        // Ensure plan_cycles rows exist for the plan so each schedule can be
        // linked to its actual cycle row (plan_cycles.id), not a cycle number.
        $cycleIds = $this->ensurePlanCycles($planId, $plan->numberOfCycles, $plan->startDate, $interval);

        $members = $this->planMembers->allForPlan($planId, 'active');
        $count = 0;

        foreach ($members as $memberIndex => $member) {
            for ($cycle = 1; $cycle <= $plan->numberOfCycles; $cycle++) {
                $dueDate = $this->addInterval(new DateTimeImmutable($plan->startDate), $interval, $cycle - 1)->format('Y-m-d');

                $this->schedules->create([
                    'plan_id'       => $planId,
                    'plan_cycle_id' => $cycleIds[$cycle] ?? null,
                    'member_id'     => $member->memberId,
                    'due_date'      => $dueDate,
                    'amount'        => $plan->contributionAmount,
                    'amount_paid'   => '0.00',
                    'status'        => 'pending',
                ]);
                $count++;
            }
        }

        // Also generate/ensure Payout Schedules for the cycles
        $pdo = Database::connection();
        $memberCount = count($members);
        if ($memberCount > 0) {
            $today = (new DateTimeImmutable())->format('Y-m-d');
            for ($cycle = 1; $cycle <= $plan->numberOfCycles; $cycle++) {
                $recipient = $members[($cycle - 1) % $memberCount];
                $payoutDate = $this->addInterval(new DateTimeImmutable($plan->startDate), $interval, $cycle - 1)->format('Y-m-d');
                $expectedAmount = $plan->payoutMode === 'fixed' && (float)$plan->fixedPayoutAmount > 0 
                    ? $plan->fixedPayoutAmount 
                    : bcmul($plan->contributionAmount, (string)$memberCount, 2);

                // Auto determine status by date
                $cycleStatus = ($payoutDate < $today) ? 'paid' : (($payoutDate === $today) ? 'due' : 'scheduled');

                $check = $pdo->prepare('SELECT id, status FROM payout_schedules WHERE plan_id = :p AND plan_cycle_id = :c LIMIT 1');
                $check->execute([':p' => $planId, ':c' => $cycleIds[$cycle] ?? 0]);
                $existingPayout = $check->fetch(PDO::FETCH_ASSOC);

                if (!$existingPayout) {
                    $insertPayout = $pdo->prepare(
                        'INSERT INTO payout_schedules (plan_id, plan_cycle_id, recipient_member_id, payout_date, expected_amount, status)
                         VALUES (:plan_id, :plan_cycle_id, :recipient_member_id, :payout_date, :expected_amount, :status)'
                    );
                    $insertPayout->execute([
                        ':plan_id'             => $planId,
                        ':plan_cycle_id'       => $cycleIds[$cycle] ?? null,
                        ':recipient_member_id' => $recipient->memberId,
                        ':payout_date'        => $payoutDate,
                        ':expected_amount'    => $expectedAmount,
                        ':status'             => $cycleStatus,
                    ]);
                } else {
                    // Update recipient and payout date if not yet paid
                    if (($existingPayout['status'] ?? '') !== 'paid') {
                        $updatePayout = $pdo->prepare(
                            'UPDATE payout_schedules 
                             SET recipient_member_id = :recipient_member_id, payout_date = :payout_date, expected_amount = :expected_amount
                             WHERE id = :id'
                        );
                        $updatePayout->execute([
                            ':recipient_member_id' => $recipient->memberId,
                            ':payout_date'        => $payoutDate,
                            ':expected_amount'    => $expectedAmount,
                            ':id'                 => $existingPayout['id'],
                        ]);
                    }
                }
            }
        }

        AuditService::log('plan.schedules.generate', $this->auth->currentUser()?->id, 'plan', $planId, [
            'schedules' => $count,
        ]);

        return ['ok' => true, 'count' => $count];
    }

    /**
     * Change a plan's status.
     *
     * @return array{ok: bool, error?: string}
     */
    public function changeStatus(int $planId, string $status): array
    {
        $plan = $this->plans->findById($planId);
        if ($plan === null) {
            return ['ok' => false, 'error' => 'Pelan tidak dijumpai.'];
        }

        $this->plans->update($planId, ['status' => $status]);

        AuditService::log('plan.status', $this->auth->currentUser()?->id, 'plan', $planId, [
            'from' => $plan->status,
            'to'   => $status,
        ]);

        return ['ok' => true];
    }

    /**
     * Aggregate statistics for the admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $activePlans = 0;
        foreach ($this->plans->countByStatus() as $status => $total) {
            if ($status === 'active' || $status === 'open') {
                $activePlans += $total;
            }
        }

        $pdo = Database::connection();

        $members = (int) $pdo->query(
            'SELECT COUNT(DISTINCT member_id) AS total FROM plan_members WHERE status = \'active\''
        )->fetchColumn();

        $summary = $this->ledger->balanceSummary();

        $overdue = (int) $pdo->query(
            "SELECT COUNT(*) AS total FROM contribution_schedules WHERE status = 'overdue'"
        )->fetchColumn();

        return [
            'active_plans'    => $activePlans,
            'total_members'   => $members,
            'total_collection' => $summary['contribution'] ?? '0.00',
            'total_payout'    => $summary['payout'] ?? '0.00',
            'admin_fee_sum'   => $summary['admin_fee'] ?? '0.00',
            'shortfall_sum'   => $summary['shortfall'] ?? '0.00',
            'overdue_count'   => $overdue,
        ];
    }

    // Internal helper kept private to reuse transaction handle when needed.
    private function pdo(): PDO
    {
        return Database::connection();
    }

    /**
     * Ensure a row exists in `plan_cycles` for every cycle of the plan, and
     * return a map of cycle_no => plan_cycles.id. Existing rows are reused.
     *
     * @return array<int, int>
     */
    private function ensurePlanCycles(int $planId, int $numberOfCycles, string $startDate, DateInterval $interval): array
    {
        $pdo = Database::connection();
        $map = [];

        for ($cycle = 1; $cycle <= $numberOfCycles; $cycle++) {
            $stmt = $pdo->prepare('SELECT id FROM plan_cycles WHERE plan_id = :plan_id AND cycle_no = :cycle_no LIMIT 1');
            $stmt->execute([':plan_id' => $planId, ':cycle_no' => $cycle]);
            $existing = (int) $stmt->fetchColumn();

            if ($existing > 0) {
                $map[$cycle] = $existing;
                continue;
            }

            $start = $this->addInterval(new DateTimeImmutable($startDate), $interval, $cycle - 1);
            $end   = $this->addInterval($start, $interval, 1)->modify('-1 day');

            $insert = $pdo->prepare(
                'INSERT INTO plan_cycles (plan_id, cycle_no, start_date, end_date, status)
                 VALUES (:plan_id, :cycle_no, :start_date, :end_date, :status)'
            );
            $insert->execute([
                ':plan_id'    => $planId,
                ':cycle_no'   => $cycle,
                ':start_date' => $start->format('Y-m-d'),
                ':end_date'   => $end->format('Y-m-d'),
                ':status'     => 'upcoming',
            ]);
            $map[$cycle] = (int) $pdo->lastInsertId();
        }

        return $map;
    }

    /**
     * Add a DateInterval a given number of times (DateTimeImmutable is
     * immutable, so this is safe across calls).
     */
    private function addInterval(DateTimeImmutable $date, DateInterval $interval, int $times): DateTimeImmutable
    {
        for ($i = 0; $i < $times; $i++) {
            $date = $date->add($interval);
        }
        return $date;
    }
}
