<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Represents a user record from the `users` table.
 */
final class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public int $roleId,
        public string $roleSlug,
        public string $status,
        public bool $mustResetPassword,
        public ?string $lastLoginAt,
        public ?string $lastLoginIp,
        public int $failedLoginCount,
        public ?string $lockedUntil,
        public ?string $rememberToken,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && strtotime($this->lockedUntil) > time();
    }

    public function isAdmin(): bool
    {
        return in_array($this->roleSlug, ['admin', 'super_admin', 'staff'], true);
    }

    public function mustResetPassword(): bool
    {
        return $this->mustResetPassword;
    }
}
