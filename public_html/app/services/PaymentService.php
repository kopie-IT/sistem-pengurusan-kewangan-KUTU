<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ContributionSchedule;
use App\Models\Member;
use App\Repositories\ContributionScheduleRepository;
use App\Repositories\CreditScoreRepository;
use App\Repositories\LedgerRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PaymentSlipRepository;
use App\Repositories\PlanMemberRepository;
use PDO;

/**
 * Handles single contribution payments submitted by a member against a
 * contribution schedule. Amounts are stored and computed with BC MATH to avoid
 * floating-point drift. Schedule state is advanced to partial/paid as the
 * member pays.
 */
final class PaymentService
{
    private const SCALE = 2;

    public function __construct(
        private ContributionScheduleRepository $schedules,
        private PaymentRepository $payments,
        private PaymentSlipRepository $slips,
        private PlanMemberRepository $planMembers,
        private LedgerRepository $ledger,
        private CreditScoreRepository $creditScores,
        private NotificationRepository $notifications,
    ) {}

    /**
     * Submit a single contribution payment.
     *
     * @return array{ok: bool, id?: int, error?: string}
     */
    public function submitSingle(int $memberId, int $planId, int $scheduleId, string $amount, ?int $slipId): array
    {
        if (bccomp($amount, '0', self::SCALE) <= 0) {
            return ['ok' => false, 'error' => 'Jumlah bayaran mestilah lebih daripada sifar.'];
        }

        $schedule = $this->schedules->findById($scheduleId);
        if ($schedule === null) {
            return ['ok' => false, 'error' => 'Jadual caruman tidak dijumpai.'];
        }

        if ($schedule->memberId !== $memberId || $schedule->planId !== $planId) {
            return ['ok' => false, 'error' => 'Jadual caruman tidak sepadan dengan ahli atau pelan.'];
        }

        if (!in_array($schedule->status, ['pending', 'overdue', 'partial'], true)) {
            return ['ok' => false, 'error' => 'Jadual caruman tidak boleh dibayar dalam keadaan ini.'];
        }

        $paymentId = $this->payments->create([
            'member_id'              => $memberId,
            'plan_id'                => $planId,
            'contribution_schedule_id' => $scheduleId,
            'amount'                 => $amount,
            'status'                 => 'submitted',
            'payment_slip_id'        => $slipId,
        ]);

        $newPaid = bcadd($schedule->amountPaid, $amount, self::SCALE);
        $newStatus = bccomp($newPaid, $schedule->amount, self::SCALE) >= 0 ? 'paid' : 'partial';
        $this->schedules->markPaid($scheduleId, $newPaid, $newStatus);

        AuditService::log('payment.submit', $memberId, 'payment', $paymentId, [
            'plan_id'     => $planId,
            'schedule_id' => $scheduleId,
            'amount'      => $amount,
            'slip_id'     => $slipId,
        ]);

        $this->notifyMember($memberId, 'payment.submitted', 'Bayaran dihantar', 'Bayaran caruman sebanyak RM ' . $amount . ' telah dihantar untuk semakan.');

        return ['ok' => true, 'id' => $paymentId];
    }

    /**
     * @return \App\Models\Payment[]
     */
    public function historyForMember(int $memberId): array
    {
        return $this->payments->allForMember($memberId);
    }

    private function notifyMember(int $memberId, string $type, string $title, string $message): void
    {
        $member = $this->loadMemberForNotify($memberId);
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

    private function loadMemberForNotify(int $memberId): ?Member
    {
        // Re-use the schedule repository indirectly is overkill; callers always
        // pass a valid member id, so resolve via the plans/members path.
        static $repo;
        if ($repo === null) {
            $repo = new \App\Repositories\MemberRepository();
        }
        return $repo->findById($memberId);
    }
}
