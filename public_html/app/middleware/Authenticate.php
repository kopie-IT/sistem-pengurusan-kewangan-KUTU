<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

/**
 * Ensures the user is authenticated. Otherwise redirects to /login.
 */
final class Authenticate
{
    public function __construct(private AuthService $auth) {}

    public function handle(): void
    {
        if ($this->auth->isAuthenticated()) {
            return;
        }
        header('Location: /login');
        exit;
    }
}
