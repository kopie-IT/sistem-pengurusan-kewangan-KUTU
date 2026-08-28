<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\MemberRepository;
use App\Repositories\PaymentSlipRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\PayoutScheduleRepository;
use App\Repositories\PlanRepository;
use App\Services\AuthService;
use App\Services\FileUploadService;
use App\Services\PayoutService;

final class PayoutController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private PayoutService $payouts,
        private PayoutScheduleRepository $schedules,
        private PayoutRepository $payoutRepo,
        private PlanRepository $plans,
        private MemberRepository $members,
        private FileUploadService $uploads,
        private PaymentSlipRepository $slips,
    ) {}

    private function currentMember(): ?\App\Models\Member
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        return $userId === 0 ? null : $this->members->findByUserId($userId);
    }

    /** Admin: payout schedules for a plan + create form. */
    public function adminIndex(): void
    {
        $planId = (int) ($_GET['plan_id'] ?? 0);
        $plan = $planId > 0 ? $this->plans->findById($planId) : null;
        $schedules = $planId > 0 ? $this->schedules->allForPlan($planId) : [];
        $payouts = $planId > 0 ? $this->payoutRepo->allForPlan($planId) : [];
        $plans = $this->plans->all();

        $this->view('payouts/admin_index', [
            'title'     => 'Urus Pembayaran',
            'plan'      => $plan,
            'plans'     => $plans,
            'schedules' => $schedules,
            'payouts'   => $payouts,
        ]);
    }

    /** Admin: create a payout schedule row. */
    public function createSchedule(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/payouts');
        }

        $planId = (int) ($_POST['plan_id'] ?? 0);
        $cycleNo = (int) ($_POST['plan_cycle_id'] ?? 0);
        $recipientMemberId = (int) ($_POST['recipient_member_id'] ?? 0);
        $payoutDate = trim((string) ($_POST['payout_date'] ?? ''));
        $expectedAmount = trim((string) ($_POST['expected_amount'] ?? ''));

        if ($planId === 0 || $recipientMemberId === 0 || $payoutDate === '' || !is_numeric($expectedAmount)) {
            set_flash('error', 'Sila isi semua maklumat jadual pembayaran.');
            $this->redirect('/admin/payouts?plan_id=' . $planId);
        }

        $this->payouts->createSchedule($planId, $cycleNo, $recipientMemberId, $payoutDate, $expectedAmount);
        set_flash('success', 'Jadual pembayaran dicipta.');
        $this->redirect('/admin/payouts?plan_id=' . $planId);
    }

    /** Admin: generate a payout from a schedule. */
    public function generate(int $scheduleId): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/payouts');
        }

        $schedule = $this->schedules->findById($scheduleId);
        if ($schedule === null) {
            set_flash('error', 'Jadual pembayaran tidak dijumpai.');
            $this->redirect('/admin/payouts');
        }

        $actualCollection = trim((string) ($_POST['actual_collection'] ?? ''));
        if (!is_numeric($actualCollection)) {
            set_flash('error', 'Jumlah kutipan sebenar mestilah nombor.');
            $this->redirect('/admin/payouts?plan_id=' . $schedule->planId);
        }

        $slipId = null;
        if (!empty($_FILES['slip']['tmp_name'])) {
            $up = $this->uploads->upload($_FILES['slip'], 'payout', $schedule->recipientMemberId, (int) $_SESSION['user_id']);
            if (!$up['ok']) {
                set_flash('error', $up['error'] ?? 'Gagal memuat naik slip.');
                $this->redirect('/admin/payouts?plan_id=' . $schedule->planId);
            }
            $slipId = $up['slip_id'];
        }

        $result = $this->payouts->generatePayout($scheduleId, $actualCollection, $slipId, (int) $_SESSION['user_id']);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal menjana pembayaran.');
            $this->redirect('/admin/payouts?plan_id=' . $schedule->planId);
        }

        set_flash('success', 'Pembayaran dijana. Bersih: ' . format_money($result['net'] ?? '0.00') . '.');
        $this->redirect('/admin/payouts?plan_id=' . $schedule->planId);
    }

    /** Admin: confirm/attach a payout slip. */
    public function confirmSlip(int $payoutId): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/payouts');
        }

        $payout = $this->payoutRepo->findById($payoutId);
        if ($payout === null) {
            set_flash('error', 'Pembayaran tidak dijumpai.');
            $this->redirect('/admin/payouts');
        }

        if (empty($_FILES['slip']['tmp_name'])) {
            set_flash('error', 'Sila muat naik slip pembayaran.');
            $this->redirect('/admin/payouts?plan_id=' . $payout->planId);
        }

        $up = $this->uploads->upload($_FILES['slip'], 'payout', $payout->recipientMemberId, (int) $_SESSION['user_id']);
        if (!$up['ok']) {
            set_flash('error', $up['error'] ?? 'Gagal memuat naik slip.');
            $this->redirect('/admin/payouts?plan_id=' . $payout->planId);
        }

        $result = $this->payouts->confirmPayoutSlip($payoutId, $up['slip_id'], (int) $_SESSION['user_id']);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Gagal mengesahkan slip.');
            $this->redirect('/admin/payouts?plan_id=' . $payout->planId);
        }

        set_flash('success', 'Slip pembayaran disahkan.');
        $this->redirect('/admin/payouts?plan_id=' . $payout->planId);
    }

    /** Member: payout calendar (upcoming + history). */
    public function memberView(): void
    {
        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/dashboard');
        }

        $upcoming = $this->schedules->allUpcomingForMember($member->id);
        $history = $this->payoutRepo->allForRecipient($member->id);

        $this->view('payouts/member_view', [
            'title'    => 'Pembayaran Saya',
            'member'   => $member,
            'upcoming' => $upcoming,
            'history'  => $history,
        ]);
    }
}
