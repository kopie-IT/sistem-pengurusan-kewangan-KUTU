<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Data access for password reset tokens.
 */
final class PasswordResetRepository
{
    public function create(int $userId, string $tokenHash, string $expiresAt, ?int $createdBy = null): int
    {
        $sql = 'INSERT INTO password_resets (user_id, token_hash, expires_at, created_by)
                VALUES (:user_id, :token, :expires, :created_by)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':user_id'    => $userId,
            ':token'      => $tokenHash,
            ':expires'    => $expiresAt,
            ':created_by' => $createdBy,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        $sql = 'SELECT pr.*, u.email AS user_email
                FROM password_resets pr
                INNER JOIN users u ON u.id = pr.user_id
                WHERE pr.token_hash = :token
                  AND pr.used_at IS NULL
                  AND pr.expires_at > NOW()
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':token' => $tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $sql = 'UPDATE password_resets SET used_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function deleteForUser(int $userId): void
    {
        $sql = 'DELETE FROM password_resets WHERE user_id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $userId]);
    }
}
