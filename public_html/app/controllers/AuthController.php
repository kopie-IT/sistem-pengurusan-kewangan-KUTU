<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private UserRepository $users,
    ) {}

    public function showLogin(): void
    {
        if ($this->auth->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/login', [
            'title' => 'Log Masuk',
            'layout' => 'layouts/auth',
        ]);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $token = (string) ($_POST['csrf_token'] ?? '');

        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $_SESSION['old']['email'] = $email;
            $this->redirect('/login');
        }

        if ($email === '' || $password === '') {
            set_flash('error', 'Sila masukkan emel dan kata laluan.');
            $_SESSION['old']['email'] = $email;
            $this->redirect('/login');
        }

        $result = $this->auth->attemptLogin($email, $password);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Log masuk gagal.');
            $_SESSION['old']['email'] = $email;
            $this->redirect('/login');
        }

        $this->auth->startSessionFor($result['user']);
        unset($_SESSION['old']);

        if ($result['user']->mustResetPassword()) {
            set_flash('info', 'Sila tetapkan kata laluan baru sebelum meneruskan.');
            $this->redirect('/reset-password');
        }

        set_flash('success', 'Selamat datang, ' . $result['user']->name . '.');
        $this->redirect('/dashboard');
    }

    public function showRegister(): void
    {
        $this->view('auth/register', [
            'title' => 'Daftar Akaun',
            'layout' => 'layouts/auth',
        ]);
    }

    public function register(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/register');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            set_flash('error', 'Sila isi semua maklumat dengan betul.');
            $this->redirect('/register');
        }

        if ($password !== $passwordConfirm) {
            set_flash('error', 'Kata laluan tidak sepadan.');
            $this->redirect('/register');
        }

        // TODO: Implement actual user creation flow in Phase 2.
        set_flash('error', 'Pendaftaran belum dibuka pada masa ini.');
        $this->redirect('/register');
    }

    // ----------------------------------------------------------------------
    // Reset password — for first-time login OR via token
    // ----------------------------------------------------------------------

    public function showResetPassword(): void
    {
        if (!$this->auth->isAuthenticated() && empty($_GET['token'])) {
            $this->redirect('/login');
        }

        $this->view('auth/reset-password', [
            'title'     => 'Tetapkan Kata Laluan',
            'layout'    => 'layouts/auth',
            'hasToken'  => !empty($_GET['token']),
        ]);
    }

    public function resetPassword(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/reset-password');
        }

        $newPassword = (string) ($_POST['password'] ?? '');
        $confirm     = (string) ($_POST['password_confirm'] ?? '');
        $resetToken  = (string) ($_POST['reset_token'] ?? '');

        if ($newPassword === '' || $newPassword !== $confirm) {
            set_flash('error', 'Kata laluan baru dan pengesahan tidak sepadan.');
            $this->redirect($this->redirectTarget($resetToken));
        }

        // Two flows:
        // 1. Logged-in user with force flag (first-time / admin reset)
        // 2. Token-based reset via link
        if ($resetToken !== '') {
            $result = $this->auth->completeTokenReset($resetToken, $newPassword);
            if (!$result['ok']) {
                set_flash('error', $result['error'] ?? 'Reset gagal.');
                $this->redirect('/login');
            }
            set_flash('success', 'Kata laluan berjaya dikemaskini. Sila log masuk.');
            $this->redirect('/login');
        }

        if (!$this->auth->isAuthenticated()) {
            $this->redirect('/login');
        }

        $userId = (int) $_SESSION['user_id'];
        $result = $this->auth->completeFirstTimeReset($userId, $newPassword);
        if (!$result['ok']) {
            set_flash('error', $result['error'] ?? 'Reset gagal.');
            $this->redirect('/reset-password');
        }

        set_flash('success', 'Kata laluan berjaya ditukar. Anda kini boleh menggunakan sistem.');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->redirect('/login');
    }

    private function redirectTarget(string $token): string
    {
        return $token !== '' ? '/reset-password?token=' . urlencode($token) : '/reset-password';
    }
}
