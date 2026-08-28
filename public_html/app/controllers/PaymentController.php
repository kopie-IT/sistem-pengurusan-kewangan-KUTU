<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ContributionScheduleRepository;
use App\Repositories\MemberRepository;
use App\Repositories\PaymentBatchRepository;
use App\Repositories\PaymentSlipRepository;
use App\Services\AuthService;
use App\Services\BulkPaymentService;
use App\Services\FileUploadService;
use App\Services\PaymentService;

final class PaymentController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private PaymentService $payments,
        private BulkPaymentService $bulk,
        private FileUploadService $uploads,
        private MemberRepository $members,
        private ContributionScheduleRepository $schedules,
        private PaymentBatchRepository $batches,
        private PaymentSlipRepository $slips,
    ) {}

    private function currentMember(): ?\App\Models\Member
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        return $userId === 0 ? null : $this->members->findByUserId($userId);
    }

    /** Member: contribution schedules + past batches. */
    public function index(): void
    {
        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/dashboard');
        }

        $all = $this->schedules->allForMember($member->id);
        $grouped = ['pending' => [], 'overdue' => [], 'partial' => [], 'paid' => []];
        foreach ($all as $s) {
            if ($s->status === 'overdue' || $s->status === 'partial') {
                $grouped[$s->status][] = $s;
            } elseif ($s->status === 'paid') {
                $grouped['paid'][] = $s;
            } else {
                $grouped['pending'][] = $s;
            }
        }

        $batchHistory = $this->batches->allForMember($member->id);

        $this->view('payments/index', [
            'title'        => 'Bayaran Caruman',
            'member'       => $member,
            'grouped'      => $grouped,
            'batches'      => $batchHistory,
        ]);
    }

    /** Member: bulk payment form listing outstanding schedules. */
    public function bulk(): void
    {
        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/dashboard');
        }

        $outstanding = $this->schedules->allForMember($member->id);
        $outstanding = array_filter($outstanding, static fn ($s) => in_array($s->status, ['pending', 'overdue', 'partial'], true));

        $this->view('payments/bulk', [
            'title'      => 'Bayaran Pukal',
            'member'     => $member,
            'schedules'  => $outstanding,
        ]);
    }

    /** Member: submit bulk payment. */
    public function submitBulk(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/payments/bulk');
        }

        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/payments/bulk');
        }

        $rawItems = $_POST['items'] ?? [];
        if (!is_array($rawItems) || $rawItems === []) {
            set_flash('error', 'Sila pilih sekurang-kurangnya satu jadual caruman.');
            $this->redirect('/payments/bulk');
        }

        $items = [];
        foreach ($rawItems as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            // Honour the "selected" checkbox: only include rows the member ticked.
            if (isset($raw['selected']) && $raw['selected'] !== '1' && $raw['selected'] !== 'on') {
                continue;
            }
            $scheduleId = (int) ($raw['schedule_id'] ?? 0);
            $planId = (int) ($raw['plan_id'] ?? 0);
            $amount = (string) ($raw['amount'] ?? '0');
            if ($scheduleId <= 0 || $planId <= 0) {
                continue;
            }
            $items[] = [
                'plan_id'     => $planId,
                'schedule_id' => $scheduleId,
                'amount'      => $amount,
            ];
        }

        if ($items === []) {
            set_flash('error', 'Tiada item bayaran yang sah dihantar.');
            $this->redirect('/payments/bulk');
        }

        $slipId = null;
        if (!empty($_FILES['slip']['tmp_name'])) {
            $up = $this->uploads->upload($_FILES['slip'], 'contribution', $member->id, (int) $_SESSION['user_id']);
            if (!$up['ok']) {
                set_flash('error', $up['error'] ?? 'Gagal memuat naik slip.');
                $this->redirect('/payments/bulk');
            }
            $slipId = $up['slip_id'];
        }

        $result = $this->bulk->submitBulk($member->id, $items, $slipId);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal menghantar bayaran pukal.');
            $this->redirect('/payments/bulk');
        }

        set_flash('success', 'Bayaran pukal berjaya dihantar (Jumlah: ' . format_money($result['total'] ?? '0.00') . ').');
        $this->redirect('/payments');
    }

    /** Member: single payment form for one schedule. */
    public function single(int $scheduleId): void
    {
        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/dashboard');
        }

        $schedule = $this->schedules->findById($scheduleId);
        if ($schedule === null || $schedule->memberId !== $member->id) {
            set_flash('error', 'Jadual caruman tidak dijumpai.');
            $this->redirect('/payments');
        }

        $this->view('payments/single', [
            'title'    => 'Bayaran Tunggal',
            'member'   => $member,
            'schedule' => $schedule,
        ]);
    }

    /** Member: submit single payment. */
    public function submitSingle(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/payments');
        }

        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/payments');
        }

        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $amount = trim((string) ($_POST['amount'] ?? ''));

        $schedule = $this->schedules->findById($scheduleId);
        if ($schedule === null || $schedule->memberId !== $member->id) {
            set_flash('error', 'Jadual caruman tidak dijumpai.');
            $this->redirect('/payments');
        }

        if (!is_numeric($amount) || (float) $amount <= 0) {
            set_flash('error', 'Jumlah bayaran mestilah nombor positif.');
            $this->redirect('/payments/' . $scheduleId);
        }

        $slipId = null;
        if (!empty($_FILES['slip']['tmp_name'])) {
            $up = $this->uploads->upload($_FILES['slip'], 'contribution', $member->id, (int) $_SESSION['user_id']);
            if (!$up['ok']) {
                set_flash('error', $up['error'] ?? 'Gagal memuat naik slip.');
                $this->redirect('/payments/' . $scheduleId);
            }
            $slipId = $up['slip_id'];
        }

        $result = $this->payments->submitSingle($member->id, $planId, $scheduleId, $amount, $slipId);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal menghantar bayaran.');
            $this->redirect('/payments/' . $scheduleId);
        }

        set_flash('success', 'Bayaran berjaya dihantar untuk semakan.');
        $this->redirect('/payments');
    }

    /** Download a payment slip (owner or admin only). */
    public function downloadSlip(int $slipId): void
    {
        if (!$this->auth->isAuthenticated()) {
            $this->redirect('/login');
        }

        $slip = $this->slips->findById($slipId);
        if ($slip === null) {
            set_flash('error', 'Slip tidak dijumpai.');
            $this->redirect('/payments');
        }

        $isAdmin = in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin', 'staff'], true);
        if (!$isAdmin && (int) ($slip->memberId ?? 0) !== (int) ($_SESSION['user_id'] ?? 0)
            && (int) ($slip->uploadedBy ?? 0) !== (int) ($_SESSION['user_id'] ?? 0)) {
            set_flash('error', 'Anda tidak dibenarkan memuat turun fail ini.');
            $this->redirect('/payments');
        }

        $path = $this->uploads->getDownloadPath($slipId);
        if ($path === null) {
            set_flash('error', 'Fail tidak wujud pada pelayan.');
            $this->redirect('/payments');
        }

        header('Content-Type: ' . e($slip->mimeType ?? 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . e($slip->originalName ?? ('slip-' . $slipId)) . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
