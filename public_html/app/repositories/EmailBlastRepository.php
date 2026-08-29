<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Data access for `email_blasts` (audit trail of admin broadcasts).
 *
 * Sending the actual email is delegated to EmailBlastService; this repo only
 * records meta + recipients count and lets the admin review history.
 */
final class EmailBlastRepository
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO email_blasts
                    (subject, message, target_role, recipient_count, status, created_by, sent_at)
                VALUES
                    (:subject, :message, :target_role, :recipient_count, :status, :created_by, :sent_at)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':subject'         => $data['subject'],
            ':message'         => $data['message'],
            ':target_role'     => $data['target_role'] ?? 'all',
            ':recipient_count' => (int) ($data['recipient_count'] ?? 0),
            ':status'          => $data['status'] ?? 'queued',
            ':created_by'      => (int) ($data['created_by'] ?? 0),
            ':sent_at'         => $data['sent_at'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?int $recipientCount = null): void
    {
        if ($recipientCount !== null) {
            $stmt = Database::connection()->prepare(
                'UPDATE email_blasts SET status = :status, recipient_count = :rc, sent_at = NOW() WHERE id = :id'
            );
            $stmt->execute([':status' => $status, ':rc' => $recipientCount, ':id' => $id]);
            return;
        }
        $stmt = Database::connection()->prepare(
            'UPDATE email_blasts SET status = :status, sent_at = NOW() WHERE id = :id'
        );
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT b.*, u.name AS creator_name, u.email AS creator_email
                FROM email_blasts b
                INNER JOIN users u ON u.id = b.created_by
                ORDER BY b.id DESC
                LIMIT :limit OFFSET :offset';
        try {
            $stmt = Database::connection()->prepare($sql);
        } catch (\PDOException $e) {
            // The `email_blasts` table may not exist yet on a fresh install
            // that skipped migration 005. Treat that as an empty history
            // instead of crashing the settings page.
            if ($this->isMissingTable($e)) {
                return [];
            }
            throw $e;
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function count(): int
    {
        try {
            return (int) Database::connection()->query('SELECT COUNT(*) FROM email_blasts')->fetchColumn();
        } catch (\PDOException $e) {
            if ($this->isMissingTable($e)) {
                return 0;
            }
            throw $e;
        }
    }

    public function isTableReady(): bool
    {
        try {
            Database::connection()->query('SELECT 1 FROM email_blasts LIMIT 1');
            return true;
        } catch (\PDOException $e) {
            if ($this->isMissingTable($e)) {
                return false;
            }
            throw $e;
        }
    }

    private function isMissingTable(\PDOException $e): bool
    {
        // MySQL 1146 / SQLSTATE 42S02 = "Base table or view not found".
        return $e->getCode() === '42S02' || str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Base table or view not found');
    }
}
