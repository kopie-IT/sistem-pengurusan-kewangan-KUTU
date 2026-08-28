<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ContributionScheduleRepository;
use App\Repositories\MemberRepository;
use App\Repositories\PayoutScheduleRepository;
use App\Services\AuthService;

final class CalendarController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private MemberRepository $members,
        private ContributionScheduleRepository $schedules,
        private PayoutScheduleRepository $payoutSchedules,
    ) {}

    private function currentMember(): ?\App\Models\Member
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        return $userId === 0 ? null : $this->members->findByUserId($userId);
    }

    /** Member: contribution calendar (read-only). */
    public function contribution(): void
    {
        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/dashboard');
        }

        $schedules = $this->schedules->allForMember($member->id);

        $this->view('calendar/contribution', [
            'title'     => 'Kalendar Caruman',
            'member'    => $member,
            'schedules' => $schedules,
        ]);
    }

    /** Member: payout calendar (read-only). */
    public function payout(): void
    {
        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/dashboard');
        }

        $schedules = $this->payoutSchedules->allUpcomingForMember($member->id);

        $this->view('calendar/payout', [
            'title'     => 'Kalendar Pembayaran',
            'member'    => $member,
            'schedules' => $schedules,
        ]);
    }
}
