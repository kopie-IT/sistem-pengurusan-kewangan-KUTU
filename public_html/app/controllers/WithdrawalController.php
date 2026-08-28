<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\MemberRepository;
use App\Repositories\PlanMemberRepository;
use App\Repositories\PlanRepository;
use App\Repositories\WithdrawalRepository;
use App\Services\AuthService;

final class WithdrawalController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private WithdrawalRepository $withdrawals,
        private MemberRepository $members,
        private PlanMemberRepository $planMembers,
        private PlanRepository $plans,
    ) {}

    private function currentMember(): ?\App\Models\Member
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        return $userId === 0 ? null : $this->members->findByUserId($userId);
    }

    /** Member: request a withdrawal (form). */
    public function request(): void
    {
        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/dashboard');
        }

        $memberships = $this->planMembers->allActiveForMember($member->id);
        $plans = [];
        foreach ($memberships as $pm) {
            $plan = $this->plans->findById($pm->planId);
            if ($plan !== null && $plan->withdrawalAllowed) {
                $plans[] = $plan;
            }
        }

        $this->view('withdrawals/request', [
            'title'  => 'Mohon Pengeluaran',
            'member' => $member,
            'plans'  => $plans,
        ]);
    }

    /** Member: submit a withdrawal request. */
    public function submitRequest(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/withdrawals/request');
        }

        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/withdrawals/request');
        }

        $planId = (int) ($_POST['plan_id'] ?? 0);
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $outstanding = trim((string) ($_POST['outstanding'] ?? ''));
        $currentCycle = (int) ($_POST['current_cycle'] ?? 0);

        if ($planId === 0 || $reason === '') {
            set_flash('error', 'Sila pilih pelan dan berikan sebab.');
            $this->redirect('/withdrawals/request');
        }

        $membership = $this->planMembers->findByPlanAndMember($planId, $member->id);
        if ($membership === null || $membership->status !== 'active') {
            set_flash('error', 'Anda bukan ahli aktif pelan ini.');
            $this->redirect('/withdrawals/request');
        }

        $this->withdrawals->create([
            'member_id'     => $member->id,
            'plan_id'       => $planId,
            'reason'        => $reason,
            'request_date'  => date('Y-m-d H:i:s'),
            'current_cycle' => $currentCycle > 0 ? $currentCycle : null,
            'outstanding'   => $outstanding !== '' ? $outstanding : null,
            'score_impact'  => -3,
            'status'        => 'pending',
        ]);

        set_flash('success', 'Permintaan pengeluaran dihantar untuk kelulusan.');
        $this->redirect('/withdrawals/me');
    }

    /** Member: my withdrawal requests. */
    public function memberIndex(): void
    {
        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/dashboard');
        }

        $requests = $this->withdrawals->allForMember($member->id);

        $this->view('withdrawals/member_index', [
            'title'    => 'Pengeluaran Saya',
            'member'   => $member,
            'requests' => $requests,
        ]);
    }

    /** Admin: pending withdrawal requests. */
    public function adminIndex(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $requests = $this->withdrawals->allPending($search !== '' ? $search : null);

        $this->view('withdrawals/admin_index', [
            'title'    => 'Pengesahan Pengeluaran',
            'requests' => $requests,
            'search'   => $search,
        ]);
    }

    /** Admin: approve or reject a withdrawal. */
    public function decide(int $id): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/withdrawals');
        }

        $action = trim((string) ($_POST['action'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $status = $action === 'approve' ? 'approved' : 'rejected';
        if (!in_array($status, ['approved', 'rejected'], true)) {
            set_flash('error', 'Tindakan tidak sah.');
            $this->redirect('/admin/withdrawals');
        }

        $this->withdrawals->update($id, [
            'status'      => $status,
            'approved_by' => (int) $_SESSION['user_id'],
            'notes'       => $notes,
        ]);

        set_flash('success', 'Permintaan pengeluaran ' . ($status === 'approved' ? 'diluluskan' : 'ditolak') . '.');
        $this->redirect('/admin/withdrawals');
    }
}
