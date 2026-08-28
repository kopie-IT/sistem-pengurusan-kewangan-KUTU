<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthService;

/**
 * Admin / staff user management.
 *
 * Restricted to users in the existing "super_admin" gate (set in web.php).
 * Provides list, create, edit role, change status, force-password-reset,
 * and delete for internal accounts only (admin / super_admin / staff).
 */
final class UserManagementController extends Controller
{
    private const ALLOWED_ROLE_SLUGS  = ['admin', 'super_admin', 'staff'];
    private const ALLOWED_STATUSES    = ['active', 'inactive', 'suspended'];

    public function __construct(
        private UserRepository $users,
        private AuthService $auth,
    ) {}

    public function index(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $rows = $this->users->listInternal($search !== '' ? $search : null);

        $this->view('admin/users/index', [
            'title'  => 'Urus Pengguna Dalaman',
            'rows'   => $rows,
            'search' => $search,
        ]);
    }

    public function create(): void
    {
        $this->view('admin/users/form', [
            'title' => 'Cipta Pengguna',
            'mode'  => 'create',
            'user'  => null,
            'role'  => 'staff',
            'status' => 'active',
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $name     = trim((string) ($_POST['name'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $roleSlug = (string) ($_POST['role'] ?? '');
        $status   = (string) ($_POST['status'] ?? 'active');
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Nama dan emel yang sah diperlukan.');
            $_SESSION['old'] = $_POST;
            $this->redirect('/admin/users/create');
        }
        if (!in_array($roleSlug, self::ALLOWED_ROLE_SLUGS, true)) {
            set_flash('error', 'Peranan tidak sah.');
            $_SESSION['old'] = $_POST;
            $this->redirect('/admin/users/create');
        }
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            $status = 'active';
        }

        // A new internal account always has a temporary password. The
        // admin chooses to type one or generates a random one below.
        if ($password === '') {
            $password = self::randomStrongPassword();
            $mustReset = true;
        } else {
            // Enforce minimum length; otherwise generate.
            if (strlen($password) < 8) {
                set_flash('error', 'Kata laluan mestilah sekurang-kurangnya 8 aksara.');
                $_SESSION['old'] = $_POST;
                $this->redirect('/admin/users/create');
            }
            $mustReset = !empty($_POST['force_reset']);
        }

        // Uniqueness check.
        if ($this->users->findByEmail($email) !== null) {
            set_flash('error', 'Emel telah digunakan.');
            $_SESSION['old'] = $_POST;
            $this->redirect('/admin/users/create');
        }

        $roleId = $this->users->roleIdBySlug($roleSlug);
        if ($roleId === null) {
            set_flash('error', 'Peranan tidak dijumpai dalam pangkalan data.');
            $this->redirect('/admin/users/create');
        }

        $newId = $this->users->create([
            'name'               => $name,
            'email'              => $email,
            'password'           => password_hash($password, PASSWORD_BCRYPT),
            'role_id'            => $roleId,
            'status'             => $status,
            'must_reset_password' => $mustReset,
        ]);

        AuditService::log('user.admin.create', $this->actorId(), 'users', $newId, [
            'role'       => $roleSlug,
            'reset_set'  => $mustReset,
        ]);

        set_flash('success', sprintf(
            'Pengguna dicipta. Kata laluan sementara: %s',
            $password
        ));
        $this->redirect('/admin/users');
    }

    public function edit(int $id): void
    {
        $row = $this->findInternal($id);
        $this->view('admin/users/form', [
            'title' => 'Sunting Pengguna',
            'mode'  => 'edit',
            'user'  => $row,
            'role'  => $row['role_slug'] ?? 'staff',
            'status' => $row['status'] ?? 'active',
        ]);
    }

    public function update(int $id): void
    {
        $this->requireCsrf();
        $row = $this->findInternal($id);
        $currentUser = $this->actorId();

        if ((int) $row['id'] === $currentUser && (string) ($_POST['status'] ?? '') === 'suspended') {
            set_flash('error', 'Anda tidak boleh menggantung akaun anda sendiri.');
            $this->redirect('/admin/users/' . $id . '/edit');
        }

        $name     = trim((string) ($_POST['name'] ?? $row['name']));
        $roleSlug = (string) ($_POST['role'] ?? $row['role_slug']);
        $status   = (string) ($_POST['status'] ?? $row['status']);

        if (!in_array($roleSlug, self::ALLOWED_ROLE_SLUGS, true)) {
            $roleSlug = (string) $row['role_slug'];
        }
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            $status = (string) $row['status'];
        }

        // Update name.
        $stmt = \App\Core\Database::connection()
            ->prepare('UPDATE users SET name = :n, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':n' => $name, ':id' => $id]);

        // Update role.
        $roleId = $this->users->roleIdBySlug($roleSlug);
        if ($roleId !== null) {
            $this->users->updateRole($id, $roleId);
        }

        // Update status.
        $this->users->updateStatus($id, $status);

        AuditService::log('user.admin.update', $currentUser, 'users', $id, [
            'role'   => $roleSlug,
            'status' => $status,
        ]);

        set_flash('success', 'Pengguna dikemaskini.');
        $this->redirect('/admin/users');
    }

    public function destroy(int $id): void
    {
        $this->requireCsrf();
        $row = $this->findInternal($id);
        $currentUser = $this->actorId();

        if ((int) $row['id'] === $currentUser) {
            set_flash('error', 'Anda tidak boleh memadam akaun anda sendiri.');
            $this->redirect('/admin/users');
        }

        // Prevent deleting the last super_admin account so the system isn't
        // locked out.
        if ($row['role_slug'] === 'super_admin') {
            $countSuper = (int) \App\Core\Database::connection()
                ->query("SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE r.slug = 'super_admin' AND u.status = 'active'")
                ->fetchColumn();
            if ($countSuper <= 1) {
                set_flash('error', 'Tidak boleh memadam super admin terakhir dalam sistem.');
                $this->redirect('/admin/users');
            }
        }

        $this->users->delete($id);
        AuditService::log('user.admin.delete', $currentUser, 'users', $id, [
            'email' => $row['email'],
            'role'  => $row['role_slug'],
        ]);

        set_flash('success', 'Pengguna dipadam.');
        $this->redirect('/admin/users');
    }

    public function resetPassword(int $id): void
    {
        $this->requireCsrf();
        $row = $this->findInternal($id);

        $tempPassword = self::randomStrongPassword();

        $this->users->updatePassword(
            $id,
            password_hash($tempPassword, PASSWORD_BCRYPT),
            true
        );

        AuditService::log('user.admin.password_reset', $this->actorId(), 'users', $id, [
            'email' => $row['email'],
        ]);

        set_flash('success', sprintf(
            'Kata laluan untuk %s telah ditetapkan semula. Kata laluan sementara: %s',
            $row['email'],
            $tempPassword
        ));
        $this->redirect('/admin/users');
    }

    /**
     * @return array<string, mixed>
     */
    private function findInternal(int $id): array
    {
        foreach ($this->users->listInternal() as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }
        set_flash('error', 'Pengguna tidak dijumpai.');
        $this->redirect('/admin/users');
    }

    private function requireCsrf(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/users');
        }
    }

    private function actorId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private static function randomStrongPassword(): string
    {
        // 12-char mixed-case + digits, easy to read aloud. Not cryptographically
        // maximal but plenty for a temporary password that the user changes
        // on first login.
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < 12; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }
}
