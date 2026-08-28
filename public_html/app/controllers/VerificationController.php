<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\MemberRepository;
use App\Repositories\PaymentBatchRepository;
use App\Repositories\PaymentRepository;
use App\Services\AuthService;
use App\Services\PaymentVerificationService;

final class VerificationController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private PaymentVerificationService $verification,
        private PaymentBatchRepository $batches,
        private PaymentRepository $payments,
        private MemberRepository $members,
    ) {}

    /** Admin: queue of batches pending verification. */
    public function queue(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $queue = $this->verification->pendingQueue($search !== '' ? $search : null);

        $this->view('verification/queue', [
            'title' => 'Sahkan Bayaran',
            'queue'  => $queue,
            'search' => $search,
        ]);
    }

    /** Admin: batch detail with items + slip. */
    public function show(int $batchId): void
    {
        $batch = $this->batches->findById($batchId);
        if ($batch === null) {
            set_flash('error', 'Kumpulan bayaran tidak dijumpai.');
            $this->redirect('/admin/payments');
        }

        $items = $this->payments->allForBatch($batchId);
        $member = $this->members->findById($batch->memberId);

        $this->view('verification/show', [
            'title'  => 'Kumpulan ' . e($batch->batchNo),
            'batch'  => $batch,
            'items'  => $items,
            'member' => $member,
        ]);
    }

    /** Admin: approve a batch. */
    public function approve(int $batchId): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/payments/' . $batchId);
        }

        $result = $this->verification->approveBatch($batchId, (int) $_SESSION['user_id']);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal meluluskan.');
            $this->redirect('/admin/payments/' . $batchId);
        }

        set_flash('success', 'Kumpulan bayaran diluluskan.');
        $this->redirect('/admin/payments');
    }

    /** Admin: reject a batch. */
    public function reject(int $batchId): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/payments/' . $batchId);
        }

        $note = trim((string) ($_POST['note'] ?? ''));
        if ($note === '') {
            set_flash('error', 'Sila berikan catatan penolakan.');
            $this->redirect('/admin/payments/' . $batchId);
        }

        $result = $this->verification->rejectBatch($batchId, (int) $_SESSION['user_id'], $note);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal menolak.');
            $this->redirect('/admin/payments/' . $batchId);
        }

        set_flash('success', 'Kumpulan bayaran ditolak.');
        $this->redirect('/admin/payments');
    }

    /** Admin: request resubmission of a batch. */
    public function resubmit(int $batchId): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/payments/' . $batchId);
        }

        $note = trim((string) ($_POST['note'] ?? ''));
        if ($note === '') {
            set_flash('error', 'Sila berikan catatan untuk penghantaran semula.');
            $this->redirect('/admin/payments/' . $batchId);
        }

        $result = $this->verification->requestResubmission($batchId, (int) $_SESSION['user_id'], $note);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal meminta penghantaran semula.');
            $this->redirect('/admin/payments/' . $batchId);
        }

        set_flash('success', 'Ahli diminta menghantar semula bayaran.');
        $this->redirect('/admin/payments');
    }
}
