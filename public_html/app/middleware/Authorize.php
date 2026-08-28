<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

/**
 * Role-based authorization middleware.
 *
 * Usage in routes:
 *   $router->get('/admin/plans', [PlanController::class, 'adminIndex'], 'admin.plans',
 *       [Authenticate::class, Authorize::class => 'admin']);
 *
 * The required role is passed via the constructor (auto-resolved by the
 * reflection-based container, which maps the first string parameter).
 */
final class Authorize
{
    public function __construct(
        private AuthService $auth,
        private string $role = 'admin',
    ) {}

    public function handle(): void
    {
        if (!$this->auth->isAuthenticated()) {
            header('Location: /login');
            exit;
        }

        $userRole = $_SESSION['user_role'] ?? null;
        $allowed = match ($this->role) {
            // Top-level configuration gate: only super-admins (no staff).
            'super_admin' => in_array($userRole, ['admin', 'super_admin'], true),
            // Admin gate: any administrative user (admin / super_admin / staff).
            'admin'       => in_array($userRole, ['admin', 'super_admin', 'staff'], true),
            'member'      => $userRole !== null,
            default       => $userRole === $this->role,
        };

        if (!$allowed) {
            // Redirect non-admins away from admin areas.
            header('Location: /dashboard');
            exit;
        }
    }
}
