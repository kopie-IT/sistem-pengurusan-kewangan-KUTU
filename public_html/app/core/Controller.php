<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function view(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    /** Currently authenticated user id (0 if not logged in). */
    protected function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    /** Resolve the member row for the authenticated user, or null. */
    protected function memberId(): ?int
    {
        $m = (new \App\Repositories\MemberRepository())->findByUserId($this->userId());
        return $m ? $m->id : null;
    }

    /** Whether the current user has an admin-level role. */
    protected function isAdmin(): bool
    {
        $role = $_SESSION['user_role'] ?? '';
        return in_array($role, ['admin', 'super_admin'], true);
    }
}
