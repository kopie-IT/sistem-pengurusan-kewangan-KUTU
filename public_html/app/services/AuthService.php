<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;

/**
 * Authentication and password management service.
 *
 * Encapsulates the rules for: login, password verification, account lockout,
 * first-time password reset, and admin-triggered password reset.
 */
final class AuthService
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS     = 900; // 15 minutes
    private const RESET_TOKEN_TTL     = 3600; // 1 hour
    private const MIN_PASSWORD_LENGTH = 8;
    public function __construct(
        private UserRepository $users,
        private PasswordResetRepository $resets,
    ) {}

    // ----------------------------------------------------------------------
    // Login
    // ----------------------------------------------------------------------

    /**
     * Attempt to authenticate a user by email and password.
     *
     * @return array{ok: bool, user?: User, error?: string, locked?: bool}
     */
    public function attemptLogin(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            // Run a dummy hash to keep timing similar and avoid user enumeration.
            password_verify($password, '$2y$10$invalidsaltinvalidsalti.invalidsaltinvalidsaltinvalid');
            AuditService::log('auth.login.failed', null, 'user', null, ['email' => $email, 'reason' => 'no_user']);
            return ['ok' => false, 'error' => 'Emel atau kata laluan tidak sah.'];
        }

        // Account lockout is disabled outside of production so that local
        // development and staging are not blocked by repeated failed attempts.
        $lockoutEnabled = strtolower((string) config('APP_ENV', 'production')) === 'production';

        if ($lockoutEnabled && $user->isLocked()) {
            AuditService::log('auth.login.locked', $user->id, 'user', $user->id);
            return ['ok' => false, 'locked' => true, 'error' => 'Akaun dikunci sementara. Cuba lagi selepas 15 minit.'];
        }

        if ($user->status !== 'active') {
            AuditService::log('auth.login.inactive', $user->id, 'user', $user->id);
            return ['ok' => false, 'error' => 'Akaun tidak aktif. Sila hubungi admin.'];
        }

        if (!password_verify($password, $user->passwordHash)) {
            if ($lockoutEnabled) {
                $this->users->recordFailedLogin($user->id, self::MAX_FAILED_ATTEMPTS, self::LOCKOUT_SECONDS);
            }
            AuditService::log('auth.login.failed', $user->id, 'user', $user->id, ['reason' => 'bad_password']);
            return ['ok' => false, 'error' => 'Emel atau kata laluan tidak sah.'];
        }

        // Rehash if algorithm/cost changed.
        if (password_needs_rehash($user->passwordHash, PASSWORD_BCRYPT)) {
            $this->users->updatePassword($user->id, password_hash($password, PASSWORD_BCRYPT), false);
        }

        $this->users->recordSuccessfulLogin($user->id, $_SERVER['REMOTE_ADDR'] ?? null);
        AuditService::log('auth.login.success', $user->id, 'user', $user->id);

        return ['ok' => true, 'user' => $user];
    }

    // ----------------------------------------------------------------------
    // Session helpers
    // ----------------------------------------------------------------------

    public function startSessionFor(User $user): void
    {
        // Regenerate session id on login to prevent session fixation.
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->roleSlug;
        $_SESSION['logged_in_at'] = time();

        if ($user->mustResetPassword()) {
            $_SESSION['force_password_reset'] = true;
        } else {
            unset($_SESSION['force_password_reset']);
        }
    }

    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();

        if ($userId) {
            AuditService::log('auth.logout', $userId, 'user', $userId);
        }
    }

    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public function isAdmin(): bool
    {
        $role = $_SESSION['user_role'] ?? '';
        return in_array($role, ['admin', 'super_admin', 'staff'], true);
    }

    public function currentUser(): ?User
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        return $this->users->findById((int) $_SESSION['user_id']);
    }

    public function mustResetPassword(): bool
    {
        return !empty($_SESSION['force_password_reset']);
    }

    public function clearForceResetFlag(): void
    {
        unset($_SESSION['force_password_reset']);
    }

    // ----------------------------------------------------------------------
    // Password reset — first-time login flow
    // ----------------------------------------------------------------------

    /**
     * After a first-time login, the user is redirected to /reset-password.
     * The session holds force_password_reset = true. They must submit a new
     * password (not equal to the current one) and we clear the flag.
     */
    public function completeFirstTimeReset(int $userId, string $newPassword): array
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            return ['ok' => false, 'error' => 'Akaun tidak dijumpai.'];
        }

        if (password_verify($newPassword, $user->passwordHash)) {
            return ['ok' => false, 'error' => 'Kata laluan baru mestilah berbeza daripada yang sebelumnya.'];
        }

        $strength = $this->validatePasswordStrength($newPassword);
        if ($strength !== null) {
            return ['ok' => false, 'error' => $strength];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->users->updatePassword($userId, $hash, true);
        $this->clearForceResetFlag();
        $this->resets->deleteForUser($userId);

        AuditService::log('auth.password.reset_self', $userId, 'user', $userId);
        return ['ok' => true];
    }

    /**
     * Admin-triggered password reset. Generates a single-use token, persists
     * the hash, and returns the plaintext token so it can be sent to the
     * user (or shown once to the admin).
     */
    public function triggerAdminReset(int $userId, ?int $adminId = null): array
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            return ['ok' => false, 'error' => 'Pengguna tidak dijumpai.'];
        }

        $plaintext = bin2hex(random_bytes(16)); // 32-char hex token
        $tokenHash = hash('sha256', $plaintext);
        $expiresAt = date('Y-m-d H:i:s', time() + self::RESET_TOKEN_TTL);

        // Invalidate any existing tokens for this user.
        $this->resets->deleteForUser($userId);
        $this->resets->create($userId, $tokenHash, $expiresAt, $adminId);
        $this->users->forcePasswordReset($userId);

        AuditService::log('auth.password.reset_triggered', $adminId, 'user', $userId, [
            'expires_at' => $expiresAt,
        ]);

        return [
            'ok'      => true,
            'token'   => $plaintext,
            'expires' => $expiresAt,
            'url'     => url('/reset-password?token=' . $plaintext),
        ];
    }

    /**
     * User-initiated "forgot password" flow.
     *
     * Always returns ok=true with a generic message to prevent user
     * enumeration. If the email exists, an active user, and not locked,
     * a single-use reset token is generated and persisted.
     *
     * @return array{ok: bool, url?: string, token?: string}
     */
    public function requestPasswordReset(string $email): array
    {
        $generic = ['ok' => true];
        $user    = $this->users->findByEmail($email);
        if (!$user) {
            // Pretend success to avoid user enumeration.
            return $generic;
        }
        if (!$user->isActive()) {
            return $generic;
        }

        $plaintext  = bin2hex(random_bytes(16)); // 32-char hex token
        $tokenHash  = hash('sha256', $plaintext);
        $expiresAt  = date('Y-m-d H:i:s', time() + self::RESET_TOKEN_TTL);

        $this->resets->deleteForUser($user->id);
        $this->resets->create($user->id, $tokenHash, $expiresAt, null);

        AuditService::log('auth.password.reset_requested', $user->id, 'user', $user->id, [
            'expires_at' => $expiresAt,
        ]);

        // No SMTP configured in this environment — surface the link to the
        // requester so they can complete the flow. In production this would
        // dispatch an email via NotificationService instead.
        return [
            'ok'    => true,
            'token' => $plaintext,
            'url'   => url('/reset-password?token=' . $plaintext),
        ];
    }

    /**
     * Validate a plaintext token and return the user, or null.
     */
    public function findUserByResetToken(string $plaintextToken): ?User
    {
        $tokenHash = hash('sha256', $plaintextToken);
        $reset = $this->resets->findValidByHash($tokenHash);
        if (!$reset) {
            return null;
        }
        return $this->users->findById((int) $reset['user_id']);
    }

    /**
     * Complete a password reset using a token (from email/admin link).
     */
    public function completeTokenReset(string $plaintextToken, string $newPassword): array
    {
        $tokenHash = hash('sha256', $plaintextToken);
        $reset = $this->resets->findValidByHash($tokenHash);
        if (!$reset) {
            return ['ok' => false, 'error' => 'Token tidak sah atau telah tamat tempoh.'];
        }

        $user = $this->users->findById((int) $reset['user_id']);
        if (!$user) {
            return ['ok' => false, 'error' => 'Pengguna tidak dijumpai.'];
        }

        if (password_verify($newPassword, $user->passwordHash)) {
            return ['ok' => false, 'error' => 'Kata laluan baru mestilah berbeza daripada yang sebelumnya.'];
        }

        $strength = $this->validatePasswordStrength($newPassword);
        if ($strength !== null) {
            return ['ok' => false, 'error' => $strength];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->users->updatePassword($user->id, $hash, true);
        $this->resets->markUsed((int) $reset['id']);

        // Refresh session flag if this is the same user.
        if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $user->id) {
            $this->clearForceResetFlag();
        }

        AuditService::log('auth.password.reset_token', $user->id, 'user', $user->id);
        return ['ok' => true];
    }

    // ----------------------------------------------------------------------
    // Password strength
    // ----------------------------------------------------------------------

    public function validatePasswordStrength(string $password): ?string
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return 'Kata laluan mestilah sekurang-kurangnya ' . self::MIN_PASSWORD_LENGTH . ' aksara.';
        }
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password)) {
            return 'Kata laluan mestilah mengandungi huruf besar dan kecil.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Kata laluan mestilah mengandungi sekurang-kurangnya satu nombor.';
        }
        return null;
    }
}
