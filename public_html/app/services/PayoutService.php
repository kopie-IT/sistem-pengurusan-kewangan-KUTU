<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Member;
use App\Models\PayoutSchedule;
use App\Models\Plan;
use App\Repositories\LedgerRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\PayoutScheduleRepository;
use App\Repositories\PlanRepository;
use App\Repositories\ShortfallRepository;
use App\Services\AdminFeeService;
use PDO;

/**
 * Handles payout schedules and payout generation. A payout is generated from a
 * payout schedule, computing gross/net/admin-fee using the plan payout mode and
 * recording the financial movements in the ledger. Shortfalls (fixed-mode only)
 * are tracked separately.
 */
final class PayoutService
{
    private const SCALE = 2;

    public function __construct(
        private PayoutRepository $payouts,
        private PayoutScheduleRepository $schedules,
        private PlanRepository $plans,
        private AdminFeeService $adminFee,
        private ShortfallRepository $shortfalls,
        private LedgerRepository $ledger,
        private \App\Repositories\CreditScoreRepository $creditScores,
        private NotificationRepository $notifications,
    ) {}

    /**
     * Create a payout schedule row.
     *
     * @return int The new payout_schedule id.
     */
    public function createSchedule(int $planId, int $cycleNo, int $recipientMemberId, string $payoutDate, string $expectedAmount): int
    {
        $id = $this->schedules->create([
            'plan_id'            => $planId,
            'plan_cycle_id'      => $cycleNo,
            'recipient_member_id' => $recipientMemberId,
            'payout_date'        => $payoutDate,
            'expected_amount'    => $expectedAmount,
            'status'             => 'scheduled',
        ]);

        AuditService::log('payout.schedule', null, 'payout_schedule', $id, [
            'plan_id' => $planId,
            'recipient_member_id' => $recipientMemberId,
            'expected_amount' => $expectedAmount,
        ]);

        return $id;
    }

    /**
     * Generate a payout from a payout schedule.
     *
     * @return array{ok: bool, payout_id?: int, gross?: string, fee?: string, net?: string, shortfall?: string, error?: string}
     */
    public function generatePayout(int $payoutScheduleId, string $actualCollection, ?int $slipId, int $actorId): array
    {
        $schedule = $this->schedules->findById($payoutScheduleId);
        if ($schedule === null) {
            return ['ok' => false, 'error' => 'Jadual pembayaran tidak dijumpai.'];
        }

        $plan = $this->plans->findById($schedule->planId);
        if ($plan === null) {
            return ['ok' => false, 'error' => 'Pelan tidak dijumpai.'];
        }

        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();

            $mode = $plan->payoutMode;
            $gross = ($mode === 'fixed') ? $plan->fixedPayoutAmount : $actualCollection;

            $feeResult = $this->adminFee->computeFee($plan->id, $gross, date('Y-m-d'));
            $fee = $feeResult['fee'] ?? '0.00';

            $net = bcsub($gross, $fee, self::SCALE);

            if ($mode === 'fixed' && bccomp($gross, $actualCollection, self::SCALE) > 0) {
                $shortfall = bcsub($gross, $actualCollection, self::SCALE);
            } else {
                $shortfall = '0.00';
            }

            $payoutId = $this->payouts->create([
                'plan_id'             => $plan->id,
                'plan_cycle_id'       => $schedule->planCycleId,
                'payout_schedule_id'  => $schedule->id,
                'recipient_member_id' => $schedule->recipientMemberId,
                'gross_payout'        => $gross,
                'actual_collection'   => $actualCollection,
                'admin_fee'           => $fee,
                'net_payout'          => $net,
                'payout_mode'         => $mode,
                'status'              => 'paid',
                'payment_slip_id'     => $slipId,
            ]);

            // Set paid date / paid_by through the dedicated update.
            $this->payouts->updateStatus($payoutId, 'paid', $actorId, date('Y-m-d H:i:s'));

            // Mark the schedule paid.
            $this->schedules->updateStatus($schedule->id, 'paid');

            // Ledger movements.
            $this->ledger->create([
                'transaction_type' => 'payout',
                'member_id'        => $schedule->recipientMemberId,
                'plan_id'          => $plan->id,
                'reference_id'     => $payoutId,
                'amount'           => $net,
                'description'      => 'Pembayaran pelan ' . $plan->name,
            ]);

            $this->ledger->create([
                'transaction_type' => 'admin_fee',
                'member_id'        => $schedule->recipientMemberId,
                'plan_id'          => $plan->id,
                'reference_id'     => $payoutId,
                'amount'           => $fee,
                'description'      => 'Fi admin pembayaran pelan ' . $plan->name,
            ]);

            $shortfallId = null;
            if (bccomp($shortfall, '0.00', self::SCALE) > 0) {
                $this->ledger->create([
                    'transaction_type' => 'shortfall',
                    'member_id'        => $schedule->recipientMemberId,
                    'plan_id'          => $plan->id,
                    'reference_id'     => $payoutId,
                    'amount'           => $shortfall,
                    'description'      => 'Kekurangan pembayaran pelan ' . $plan->name,
                ]);

                $shortfallId = $this->shortfalls->create([
                    'plan_id'            => $plan->id,
                    'plan_cycle_id'      => $schedule->planCycleId,
                    'payout_id'          => $payoutId,
                    'expected_amount'    => $gross,
                    'actual_collection'  => $actualCollection,
                    'shortfall_amount'   => $shortfall,
                    'status'             => 'open',
                ]);

                // Link the shortfall back onto the payout.
                $this->payouts->update($payoutId, [
                    'shortfall_amount' => $shortfall,
                    'shortfall_id'     => $shortfallId,
                ]);
            }

            AuditService::log('payout.approve', $actorId, 'payout', $payoutId, [
                'plan_id'   => $plan->id,
                'gross'     => $gross,
                'admin_fee' => $fee,
                'net'       => $net,
                'shortfall' => $shortfall,
            ]);

            $this->notifyRecipient($schedule->recipientMemberId, 'payout.paid', 'Pembayaran dikeluarkan', 'Pembayaran anda sebanyak RM ' . $net . ' telah dikeluarkan untuk pelan ' . $plan->name . '.');

            $pdo->commit();
            return [
                'ok'        => true,
                'payout_id' => $payoutId,
                'gross'     => $gross,
                'fee'       => $fee,
                'net'       => $net,
                'shortfall' => $shortfall,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[PayoutService] generatePayout: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Gagal menjana pembayaran: ' . $e->getMessage()];
        }
    }

    /**
     * Confirm (attach) a payment slip to an already generated payout.
     *
     * @return array{ok: bool, error?: string}
     */
    public function confirmPayoutSlip(int $payoutId, int $slipId, int $actorId): array
    {
        $payout = $this->payouts->findById($payoutId);
        if ($payout === null) {
            return ['ok' => false, 'error' => 'Pembayaran tidak dijumpai.'];
        }

        $this->payouts->update($payoutId, ['payment_slip_id' => $slipId]);

        AuditService::log('payout.slip', $actorId, 'payout', $payoutId, [
            'slip_id' => $slipId,
        ]);

        return ['ok' => true];
    }

    /**
     * @return \App\Models\Payout[]
     */
    public function listForPlan(int $planId, ?string $status = null): array
    {
        return $this->payouts->allForPlan($planId, $status);
    }

    /**
     * @return PayoutSchedule[]
     */
    public function upcomingForMember(int $memberId): array
    {
        return $this->schedules->allUpcomingForMember($memberId);
    }

    private function notifyRecipient(int $memberId, string $type, string $title, string $message): void
    {
        $repo = new \App\Repositories\MemberRepository();
        $member = $repo->findById($memberId);
        if ($member === null) {
            return;
        }
        $this->notifications->create([
            'recipient_id'   => $member->userId,
            'type'           => $type,
            'title'          => $title,
            'message'        => $message,
            'reference_type' => 'member',
            'reference_id'   => $member->id,
            'channel'        => 'in_app',
            'is_read'        => false,
        ]);
    }
}
