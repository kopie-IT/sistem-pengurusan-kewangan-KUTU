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
use PDO;

/**
 * Verifies (approves / rejects / requests resubmission of) bulk payment
 * batches. Approval moves every contained payment to approved, marks the
 * schedules fully paid, writes ledger contribution rows and grants an on-time
 * payment credit-score event per distinct member.
 */
final class PaymentVerificationService
{
    private const SCALE = 2;

    public function __construct(
        private PaymentBatchRepository $batches,
        private PaymentRepository $payments,
        private ContributionScheduleRepository $schedules,
        private LedgerRepository $ledger,
        private CreditScoreRepository $creditScores,
        private NotificationRepository $notifications,
    ) {}

    /**
     * @return \App\Models\PaymentBatch[]
     */
    public function pendingQueue(?string $search = null): array
    {
        return $this->batches->allPendingVerification($search);
    }

    /**
     * Approve a batch: approve payments, settle schedules, post ledger and
     * credit-score events for each distinct member.
     *
     * @return array{ok: bool, error?: string}
     */
    public function approveBatch(int $batchId, int $actorId): array
    {
        $batch = $this->batches->findById($batchId);
        if ($batch === null) {
            return ['ok' => false, 'error' => 'Kumpulan bayaran tidak dijumpai.'];
        }

        $payments = $this->payments->allForBatch($batchId);
        if ($payments === []) {
            return ['ok' => false, 'error' => 'Kumpulan bayaran tidak mempunyai item bayaran.'];
        }

        $pdo = Database::connection();

        try {
            $pdo->beginTransaction();

            $this->batches->update($batchId, [
                'status'      => 'approved',
                'verified_by' => $actorId,
                'verified_at' => date('Y-m-d H:i:s'),
            ]);

            $memberPlan = [];

            foreach ($payments as $payment) {
                $this->payments->update($payment->id, [
                    'status'      => 'approved',
                    'verified_by' => $actorId,
                    'verified_at' => date('Y-m-d H:i:s'),
                ]);

                if ($payment->contributionScheduleId !== null) {
                    $schedule = $this->schedules->findById($payment->contributionScheduleId);
                    if ($schedule !== null) {
                        $this->schedules->markPaid($schedule->id, $payment->amount, 'paid');
                        $memberPlan[$payment->memberId][$payment->planId] = $payment->planId;
                    }
                }

                $this->ledger->create([
                    'transaction_type' => 'contribution',
                    'member_id'        => $payment->memberId,
                    'plan_id'          => $payment->planId,
                    'reference_id'     => $batchId,
                    'amount'           => $payment->amount,
                    'description'      => 'Caruman pelan ' . $payment->planId,
                ]);
            }

            // One on-time credit-score event per distinct member in the batch.
            foreach (array_keys($memberPlan) as $memberId) {
                $this->applyOnTimePayment((int) $memberId);
            }

            AuditService::log('payment.verify', $actorId, 'payment_batch', $batchId, [
                'payments' => count($payments),
            ]);

            // Notify each affected member.
            foreach (array_keys($memberPlan) as $memberId) {
                $this->notifyMember((int) $memberId, 'payment.approved', 'Bayaran diluluskan', 'Bayaran caruman anda telah diluluskan dan dikira dalam caruman pelan.');
            }

            $pdo->commit();
            return ['ok' => true];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[PaymentVerificationService] approveBatch: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Gagal meluluskan kumpulan bayaran: ' . $e->getMessage()];
        }
    }

    /**
     * Reject a batch: payments rejected, schedules returned to pending.
     *
     * @return array{ok: bool, error?: string}
     */
    public function rejectBatch(int $batchId, int $actorId, string $note): array
    {
        $batch = $this->batches->findById($batchId);
        if ($batch === null) {
            return ['ok' => false, 'error' => 'Kumpulan bayaran tidak dijumpai.'];
        }

        $payments = $this->payments->allForBatch($batchId);
        $memberIds = [];

        try {
            $this->batches->update($batchId, [
                'status'      => 'rejected',
                'verified_by' => $actorId,
                'verified_at' => date('Y-m-d H:i:s'),
                'note'        => $note,
            ]);

            foreach ($payments as $payment) {
                $this->payments->update($payment->id, [
                    'status'      => 'rejected',
                    'verified_by' => $actorId,
                    'verified_at' => date('Y-m-d H:i:s'),
                    'note'        => $note,
                ]);

                if ($payment->contributionScheduleId !== null) {
                    $schedule = $this->schedules->findById($payment->contributionScheduleId);
                    if ($schedule !== null) {
                        // Send the schedule back to pending; amount_paid unchanged.
                        $this->schedules->updateStatus($schedule->id, 'pending');
                    }
                }

                $memberIds[$payment->memberId] = $payment->memberId;
            }

            AuditService::log('payment.reject', $actorId, 'payment_batch', $batchId, ['note' => $note]);

            foreach ($memberIds as $memberId) {
                $this->notifyMember((int) $memberId, 'payment.rejected', 'Bayaran ditolak', 'Bayaran caruman anda telah ditolak. Sebab: ' . $note);
            }

            return ['ok' => true];
        } catch (\Throwable $e) {
            error_log('[PaymentVerificationService] rejectBatch: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Gagal menolak kumpulan bayaran: ' . $e->getMessage()];
        }
    }

    /**
     * Request the member to resubmit the batch.
     *
     * @return array{ok: bool, error?: string}
     */
    public function requestResubmission(int $batchId, int $actorId, string $note): array
    {
        $batch = $this->batches->findById($batchId);
        if ($batch === null) {
            return ['ok' => false, 'error' => 'Kumpulan bayaran tidak dijumpai.'];
        }

        $payments = $this->payments->allForBatch($batchId);
        $memberIds = [];

        try {
            $this->batches->update($batchId, [
                'status'      => 'resubmission',
                'verified_by' => $actorId,
                'verified_at' => date('Y-m-d H:i:s'),
                'note'        => $note,
            ]);

            foreach ($payments as $payment) {
                $this->payments->update($payment->id, [
                    'status'      => 'resubmission',
                    'verified_by' => $actorId,
                    'verified_at' => date('Y-m-d H:i:s'),
                    'note'        => $note,
                ]);
                $memberIds[$payment->memberId] = $payment->memberId;
            }

            AuditService::log('payment.resubmission', $actorId, 'payment_batch', $batchId, ['note' => $note]);

            foreach ($memberIds as $memberId) {
                $this->notifyMember((int) $memberId, 'payment.resubmission', 'Bayaran perlu dihantar semula', 'Sila hantar semula bayaran caruman anda. Catatan: ' . $note);
            }

            return ['ok' => true];
        } catch (\Throwable $e) {
            error_log('[PaymentVerificationService] requestResubmission: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Gagal meminta penghantaran semula: ' . $e->getMessage()];
        }
    }

    /**
     * Apply an ON_TIME_PAYMENT credit-score event for a member, falling back to
     * a +2 clamp on the [0, 100] range if no rule is configured.
     */
    private function applyOnTimePayment(int $memberId): void
    {
        $rule = $this->creditScores->ruleByCode('ON_TIME_PAYMENT');
        if ($rule === null) {
            $rule = $this->creditScores->ruleByCode('PAYMENT_ON_TIME');
        }

        $previous = $this->currentScore($memberId);

        if ($rule !== null) {
            $change = (int) $rule['score_change'];
            $newScore = $this->clamp($previous + $change);
            $this->creditScores->addHistory([
                'member_id'      => $memberId,
                'event'          => $rule['description'] ?? 'Pembayaran tepat pada masa',
                'reason_code'    => $rule['reason_code'],
                'previous_score' => $previous,
                'score_change'   => $newScore - $previous,
                'new_score'      => $newScore,
                'actor_id'       => null,
            ]);
            $this->creditScores->upsert($memberId, $newScore, $this->levelFor($newScore));
            return;
        }

        // Fallback: +2, clamped to [0, 100].
        $newScore = $this->clamp($previous + 2);
        $this->creditScores->upsert($memberId, $newScore, $this->levelFor($newScore));
        $this->creditScores->addHistory([
            'member_id'     => $memberId,
            'event'         => 'Pembayaran tepat pada masa',
            'reason_code'   => 'ON_TIME_PAYMENT',
            'previous_score' => $previous,
            'score_change'  => $newScore - $previous,
            'new_score'     => $newScore,
            'actor_id'      => null,
        ]);
    }

    private function currentScore(int $memberId): int
    {
        $row = $this->creditScores->findByMember($memberId);
        return $row !== null ? $row->score : 100;
    }

    private function clamp(int $score): int
    {
        if ($score < 0) {
            return 0;
        }
        if ($score > 100) {
            return 100;
        }
        return $score;
    }

    private function levelFor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 80 => 'good',
            $score >= 70 => 'fair',
            $score >= 60 => 'risk',
            default      => 'high_risk',
        };
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
