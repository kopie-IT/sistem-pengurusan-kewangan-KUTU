<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\MemberRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\CreditScoreService;
use PDO;

final class ProfileController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private MemberRepository $members,
        private UserRepository $users,
        private CreditScoreService $credit,
    ) {}

    private function currentMember(): ?\App\Models\Member
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        return $userId === 0 ? null : $this->members->findByUserId($userId);
    }

    public function index(): void
    {
        $user = $this->auth->currentUser();
        $member = $this->currentMember();
        $score = $member !== null ? $this->credit->getScore($member->id) : ['score' => 100, 'level' => 'excellent'];

        $this->view('profile/index', [
            'title'  => 'Profil Saya',
            'user'   => $user,
            'member' => $member,
            'score'  => $score,
        ]);
    }

    public function update(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/profile');
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $ic = trim((string) ($_POST['ic_number'] ?? ''));

        if ($name === '') {
            set_flash('error', 'Nama tidak boleh kosong.');
            $this->redirect('/profile');
        }

        $pdo = Database::connection();
        $pdo->prepare('UPDATE users SET name = :name, updated_at = NOW() WHERE id = :id')
            ->execute([':name' => $name, ':id' => $userId]);

        $member = $this->members->findByUserId($userId);
        if ($member !== null) {
            $this->members->update($member->id, [
                'phone'      => $phone !== '' ? $phone : null,
                'address'    => $address !== '' ? $address : null,
                'ic_number'  => $ic !== '' ? $ic : null,
            ]);
        }

        set_flash('success', 'Profil berjaya dikemaskini.');
        $this->redirect('/profile');
    }
}
