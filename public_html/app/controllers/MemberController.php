<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\CreditScoreRepository;
use App\Repositories\MemberRepository;
use App\Repositories\PlanMemberRepository;
use App\Services\AuthService;
use App\Services\CreditScoreService;
use PDO;

final class MemberController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private MemberRepository $members,
        private PlanMemberRepository $planMembers,
        private CreditScoreService $credit,
        private CreditScoreRepository $creditRepo,
    ) {}

    /** Admin: list members with search. */
    public function index(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $members = $this->members->all($perPage, ($page - 1) * $perPage, $search !== '' ? $search : null);

        $this->view('members/index', [
            'title'   => 'Ahli',
            'members' => $members,
            'search'  => $search,
            'page'    => $page,
        ]);
    }

    /** Admin: member profile + credit score + plans. */
    public function show(int $id): void
    {
        $member = $this->members->findById($id);
        if ($member === null) {
            set_flash('error', 'Ahli tidak dijumpai.');
            $this->redirect('/admin/members');
        }

        $plans = $this->planMembers->allForMember($id);
        $score = $this->credit->getScore($id);
        $history = $this->credit->getHistory($id);

        $this->view('members/show', [
            'title'   => e($member->name),
            'member'  => $member,
            'plans'   => $plans,
            'score'   => $score,
            'history' => $history,
        ]);
    }

    /** Admin: create member form. */
    public function create(): void
    {
        $this->view('members/form', [
            'title'  => 'Cipta Ahli',
            'member' => null,
        ]);
    }

    /** Admin: create user + member row. */
    public function store(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/members/create');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $ic = trim((string) ($_POST['ic_number'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Sila isi nama dan emel yang sah.');
            $_SESSION['old'] = $_POST;
            $this->redirect('/admin/members/create');
        }

        $pdo = Database::connection();
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute([':email' => $email]);
        if ($exists->fetchColumn() !== false) {
            set_flash('error', 'Emel telah digunakan.');
            $_SESSION['old'] = $_POST;
            $this->redirect('/admin/members/create');
        }

        $roleId = (int) $pdo->query("SELECT id FROM roles WHERE slug = 'member' LIMIT 1")->fetchColumn();
        if ($roleId === 0) {
            $roleId = 2; // sensible fallback
        }

        $tempPassword = bin2hex(random_bytes(6));
        $hash = password_hash($tempPassword, PASSWORD_BCRYPT);

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO users (name, email, password, role_id, status, must_reset_password, created_at, updated_at)
                 VALUES (:name, :email, :pw, :role, :status, 1, NOW(), NOW())'
            )->execute([
                ':name'   => $name,
                ':email'  => $email,
                ':pw'     => $hash,
                ':role'   => $roleId,
                ':status' => 'active',
            ]);
            $userId = (int) $pdo->lastInsertId();

            $memberId = $this->members->create([
                'user_id'      => $userId,
                'member_code'  => 'M' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT),
                'phone'        => $phone !== '' ? $phone : null,
                'ic_number'    => $ic !== '' ? $ic : null,
                'address'      => $address !== '' ? $address : null,
                'credit_score' => 100,
                'status'       => 'active',
            ]);

            $this->creditRepo->upsert($memberId, 100, 'excellent');

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[MemberController::store] ' . $e->getMessage());
            set_flash('error', 'Gagal mencipta ahli.');
            $this->redirect('/admin/members/create');
        }

        set_flash('success', 'Ahli dicipta. Kata laluan sementara: ' . $tempPassword);
        $this->redirect('/admin/members/' . $memberId);
    }

    private function currentMember(): ?\App\Models\Member
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId === 0) {
            return null;
        }
        return $this->members->findByUserId($userId);
    }
}
