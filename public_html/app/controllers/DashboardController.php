<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

final class DashboardController extends Controller
{
    private AuthService $auth;
    private ?\App\Repositories\MemberRepository $members;
    private ?\App\Services\CreditScoreService $credit;

    public function __construct(
        AuthService $auth,
        ?\App\Repositories\MemberRepository $members = null,
        ?\App\Services\CreditScoreService $credit = null,
    ) {
        $this->auth = $auth;
        $this->members = $members ?? new \App\Repositories\MemberRepository();
        $this->credit = $credit ?? new \App\Services\CreditScoreService(new \App\Repositories\CreditScoreRepository());
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

        $this->view('dashboard/index', [
            'title'  => 'Dashboard',
            'user'   => $user,
            'member' => $member,
            'score'  => $score,
        ]);
    }
}
