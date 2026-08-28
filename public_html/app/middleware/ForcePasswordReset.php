<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

/**
 * Middleware that enforces a password reset if the current session
 * carries the `force_password_reset` flag.
 *
 * If the flag is set and the request is not already on the reset-password
 * route, redirect to /reset-password.
 */
final class ForcePasswordReset
{
    public function __construct(private AuthService $auth) {}

    public function handle(): void
    {
        if (!$this->auth->isAuthenticated()) {
            return;
        }

        if (!$this->auth->mustResetPassword()) {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $allowed = ['/reset-password', '/logout'];

        foreach ($allowed as $a) {
            if ($path === $a || str_starts_with($path, $a . '?')) {
                return;
            }
        }

        header('Location: /reset-password');
        exit;
    }
}
