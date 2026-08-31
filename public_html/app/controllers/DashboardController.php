<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ContributionScheduleRepository;
use App\Services\AuthService;

final class DashboardController extends Controller
{
    private AuthService $auth;
    private ?\App\Repositories\MemberRepository $members;
    private ?\App\Services\CreditScoreService $credit;
    private ContributionScheduleRepository $schedules;

    public function __construct(
        AuthService $auth,
        ?\App\Repositories\MemberRepository $members = null,
        ?\App\Services\CreditScoreService $credit = null,
        ?ContributionScheduleRepository $schedules = null,
    ) {
        $this->auth = $auth;
        $this->members = $members ?? new \App\Repositories\MemberRepository();
        $this->credit = $credit ?? new \App\Services\CreditScoreService(new \App\Repositories\CreditScoreRepository());
        $this->schedules = $schedules ?? new ContributionScheduleRepository();
    }

    public function index(): void
    {
        $user = $this->auth->currentUser();
        $member = null;
        $score = ['score' => null, 'level' => 'unknown'];

        if ($user !== null) {
            $member = $this->members->findByUserId((int) $user->id);
            if ($member !== null) {
                $score = $this->credit->getScore($member->id);
            }
        }

        // Pull the member's own overdue / not-yet-paid contribution rows
        // so they can see what is outstanding without having to dig into
        // the calendar page. `null` member id = nothing to show.
        $unpaidSchedules = [];
        $unpaidSummary   = ['count' => 0, 'total' => '0.00'];
        if ($member !== null) {
            $unpaidSchedules = $this->schedules->findUnpaidWithDetails((int) $member->id, 10);
            $unpaidSummary   = $this->schedules->unpaidSummary((int) $member->id);
        }

        $this->view('dashboard/index', [
            'title'           => 'Dashboard',
            'user'            => $user,
            'member'          => $member,
            'score'           => $score,
            'unpaidSchedules' => $unpaidSchedules,
            'unpaidSummary'   => $unpaidSummary,
        ]);
    }
}
