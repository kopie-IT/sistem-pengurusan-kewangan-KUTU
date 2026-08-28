<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\MemberRepository;
use App\Services\AuthService;
use App\Services\CreditScoreService;

final class CreditScoreController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private CreditScoreService $credit,
        private MemberRepository $members,
    ) {}

    private function currentMember(): ?\App\Models\Member
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        return $userId === 0 ? null : $this->members->findByUserId($userId);
    }

    /** Show score + history for a member (admin or self). */
    public function show(int $memberId): void
    {
        $isAdmin = in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin', 'staff'], true);
        $member = $this->members->findById($memberId);
        if ($member === null) {
            set_flash('error', 'Ahli tidak dijumpai.');
            $this->redirect($isAdmin ? '/admin/members' : '/credit-score');
        }

        // Authorize: admin OR the member owns the record.
        $self = $this->currentMember();
        if (!$isAdmin && ($self === null || $self->id !== $memberId)) {
            set_flash('error', 'Anda tidak dibenarkan melihat skor ini.');
            $this->redirect('/credit-score');
        }

        $score = $this->credit->getScore($memberId);
        $history = $this->credit->getHistory($memberId);

        $this->view('credit_score/show', [
            'title'   => 'Skor Kredit: ' . e($member->name),
            'member'  => $member,
            'score'   => $score,
            'history' => $history,
        ]);
    }

    /** Member: their own score. */
    public function memberShow(): void
    {
        $member = $this->currentMember();
        if ($member === null) {
            set_flash('error', 'Akaun ahli tidak dijumpai.');
            $this->redirect('/dashboard');
        }
        $this->show($member->id);
    }

    /** Admin: list all members' credit scores. */
    public function adminIndex(): void
    {
        $rows = $this->credit->listAll();
        $this->view('credit_score/admin_index', [
            'title' => 'Skor Kredit Ahli',
            'rows'  => $rows,
        ]);
    }
}
