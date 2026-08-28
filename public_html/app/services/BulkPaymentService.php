<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\ContributionScheduleRepository;
use App\Repositories\CreditScoreRepository;
use App\Repositories\LedgerRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PaymentBatchRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PaymentSlipRepository;
use App\Repositories\PlanMemberRepository;
use PDO;
use RuntimeException;

/**
 * Handles batch (bulk) contribution payments: a member submits many schedule
 * payments in one batch. The whole batch is wrapped in a DB transaction so a
 * failure rolls back every payment and schedule update.
 */
final class BulkPaymentService
{
    private const SCALE = 2;

    public function __construct(
        private PaymentBatchRepository $batches,
        private PaymentRepository $payments,
        private PaymentSlipRepository $slips,
        private ContributionScheduleRepository $schedules,
        private PlanMemberRepository $planMembers,
        private LedgerRepository $ledger,
        private CreditScoreRepository $creditScores,
        private NotificationRepository $notifications,
    ) {}

    /**
     * Sum the amounts of the provided items using BC MATH.
     */
    public function computeTotal(array $items): string
    {
        $total = '0.00';
        foreach ($items as $item) {
            $total = bcadd($total, (string) ($item['amount'] ?? '0'), self::SCALE);
        }
        return $total;
    }

    /**
     * Submit a bulk payment batch.
     *
     * @param array<int, array{plan_id: int, schedule_id: int, amount: string}> $items
     * @return array{ok: bool, batch_id?: int, total?: string, error?: string}
     */
    public function submitBulk(int $memberId, array $items, ?int $slipId): array
    {
        if ($items === []) {
            return ['ok' => false, 'error' => 'Tiada item bayaran dihantar.'];
        }

        $resolved = [];
        foreach ($items as $item) {
            $planId = (int) ($item['plan_id'] ?? 0);
            $scheduleId = (int) ($item['schedule_id'] ?? 0);
            $amount = (string) ($item['amount'] ?? '0');

            if (bccomp($amount, '0', self::SCALE) <= 0) {
                return ['ok' => false, 'error' => 'Jumlah setiap item mestilah lebih daripada sifar.'];
            }

            $schedule = $this->schedules->findById($scheduleId);
            if ($schedule === null) {
                return ['ok' => false, 'error' => 'Satu atau lebih jadual caruman tidak dijumpai.'];
            }

            if ($schedule->memberId !== $memberId || $schedule->planId !== $planId) {
                return ['ok' => false, 'error' => 'Satu atau lebih jadual caruman tidak sepadan dengan ahli.'];
            }

            $resolved[] = ['plan_id' => $planId, 'schedule' => $schedule, 'amount' => $amount];
        }

        $pdo = Database::connection();
        $total = $this->computeTotal($items);

        try {
            $pdo->beginTransaction();

            $batchNo = 'BP' . date('Ymd') . '-' . bin2hex(random_bytes(3));
            $batchId = $this->batches->create([
                'batch_no'        => $batchNo,
                'member_id'       => $memberId,
                'total_amount'    => $total,
                'payment_slip_id' => $slipId,
                'status'          => 'submitted',
            ]);

            foreach ($resolved as $entry) {
                $schedule = $entry['schedule'];
                $amount = $entry['amount'];

                // payment_batch_items
                $pdo->prepare(
                    'INSERT INTO payment_batch_items (batch_id, plan_id, contribution_schedule_id, amount)
                     VALUES (:batch_id, :plan_id, :contribution_schedule_id, :amount)'
                )->execute([
                    ':batch_id'                => $batchId,
                    ':plan_id'                 => $entry['plan_id'],
                    ':contribution_schedule_id' => $schedule->id,
                    ':amount'                  => $amount,
                ]);

                $this->payments->create([
                    'member_id'              => $memberId,
                    'plan_id'                => $entry['plan_id'],
                    'contribution_schedule_id' => $schedule->id,
                    'batch_id'               => $batchId,
                    'amount'                 => $amount,
                    'status'                 => 'submitted',
                    'payment_slip_id'        => $slipId,
                ]);

                $newPaid = bcadd($schedule->amountPaid, $amount, self::SCALE);
                $newStatus = bccomp($newPaid, $schedule->amount, self::SCALE) >= 0 ? 'paid' : 'partial';
                $this->schedules->markPaid($schedule->id, $newPaid, $newStatus);
            }

            AuditService::log('payment.bulk', $memberId, 'payment_batch', $batchId, [
                'items' => count($resolved),
                'total' => $total,
                'slip_id' => $slipId,
            ]);

            $this->notifyMember($memberId, 'payment.submitted', 'Bayaran pukal dihantar', 'Bayaran pukal berjumlah RM ' . $total . ' telah dihantar untuk semakan.');

            $pdo->commit();
            return ['ok' => true, 'batch_id' => $batchId, 'total' => $total];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[BulkPaymentService] ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Gagal menghantar bayaran pukal: ' . $e->getMessage()];
        }
    }

    private function notifyMember(int $memberId, string $type, string $title, string $message): void
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
