<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

/**
 * Data access for the `users` table.
 */
final class UserRepository
{
    public function findById(int $id): ?User
    {
        $sql = 'SELECT u.*, r.slug AS role_slug
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE u.id = :id
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $sql = 'SELECT u.*, r.slug AS role_slug
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE u.email = :email
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function recordSuccessfulLogin(int $userId, ?string $ip): void
    {
        $sql = 'UPDATE users
                SET last_login_at = NOW(),
                    last_login_ip = :ip,
                    failed_login_count = 0,
                    locked_until = NULL
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':ip' => $ip, ':id' => $userId]);
    }

    public function recordFailedLogin(int $userId, int $maxAttempts, int $lockoutSeconds): void
    {
        $sql = 'UPDATE users
                SET failed_login_count = failed_login_count + 1,
                    locked_until = IF(failed_login_count + 1 >= :max_attempts,
                                       DATE_ADD(NOW(), INTERVAL :lockout SECOND),
                                       locked_until)
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':max_attempts' => $maxAttempts,
            ':lockout'      => $lockoutSeconds,
            ':id'           => $userId,
        ]);
    }

    public function updatePassword(int $userId, string $newHash, bool $clearResetFlag = true): void
    {
        $sql = 'UPDATE users
                SET password = :pw' .
                ($clearResetFlag ? ', must_reset_password = 0' : '') .
                ', updated_at = NOW()
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':pw' => $newHash, ':id' => $userId]);
    }

    public function forcePasswordReset(int $userId): void
    {
        $sql = 'UPDATE users SET must_reset_password = 1, updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $userId]);
    }

    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            name: $row['name'],
            email: $row['email'],
            passwordHash: $row['password'],
            roleId: (int) $row['role_id'],
            roleSlug: $row['role_slug'],
            status: $row['status'],
            mustResetPassword: (bool) $row['must_reset_password'],
            lastLoginAt: $row['last_login_at'] ?? null,
            lastLoginIp: $row['last_login_ip'] ?? null,
            failedLoginCount: (int) $row['failed_login_count'],
            lockedUntil: $row['locked_until'] ?? null,
            rememberToken: $row['remember_token'] ?? null,
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }
}
