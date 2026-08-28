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
    /**
     * List admin / staff users (internal accounts) for the user-management
     * page. Returns lightweight associative rows so the view can render a
     * table without hydrating full User objects.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listInternal(?string $search = null): array
    {
        $where = "WHERE r.slug IN ('admin','super_admin','staff')";
        $params = [];
        if ($search !== null && $search !== '') {
            $where .= ' AND (u.name LIKE :search OR u.email LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $sql = "SELECT u.id, u.name, u.email, u.status, u.last_login_at,
                       u.failed_login_count, u.must_reset_password,
                       r.slug AS role_slug, r.name AS role_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                {$where}
                ORDER BY FIELD(r.slug, 'super_admin','admin','staff'), u.name ASC";

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO users (name, email, password, role_id, status, must_reset_password, created_at, updated_at)
                VALUES (:name, :email, :password, :role_id, :status, :must_reset, NOW(), NOW())';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':name'        => $data['name'],
            ':email'       => $data['email'],
            ':password'    => $data['password'],
            ':role_id'     => (int) $data['role_id'],
            ':status'      => $data['status'] ?? 'active',
            ':must_reset'  => (int) ($data['must_reset_password'] ?? 1),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateRole(int $userId, int $roleId): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET role_id = :r, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':r' => $roleId, ':id' => $userId]);
    }

    public function updateStatus(int $userId, string $status): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET status = :s, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':s' => $status, ':id' => $userId]);
    }

    public function forceResetPassword(int $userId): void
    {
        $this->forcePasswordReset($userId);
    }

    public function delete(int $userId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }

    public function roleIdBySlug(string $slug): ?int
    {
        $stmt = Database::connection()->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : (int) $v;
    }

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

    public function updateProfile(int $userId, string $name, ?string $avatarPath = null): void
    {
        // Avatar is updated separately via updateAvatar(); here we just keep
        // the basic textual profile fields in sync (currently just the name).
        $stmt = Database::connection()->prepare(
            'UPDATE users SET name = :name, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([':name' => $name, ':id' => $userId]);
    }

    public function updateAvatar(int $userId, ?string $storedName): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET avatar_path = :p, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([':p' => $storedName, ':id' => $userId]);
    }

    public function getAvatarPath(int $userId): ?string
    {
        $stmt = Database::connection()->prepare(
            'SELECT avatar_path FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $v = $stmt->fetchColumn();
        return ($v === false || $v === null || $v === '') ? null : (string) $v;
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
