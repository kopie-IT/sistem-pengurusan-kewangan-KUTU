<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Notification;
use PDO;

/**
 * Data access for the `notifications` table.
 */
final class NotificationRepository
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO notifications (
                    recipient_id, type, title, message, reference_type, reference_id, channel, is_read
                ) VALUES (
                    :recipient_id, :type, :title, :message, :reference_type, :reference_id, :channel, :is_read
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':recipient_id'  => $data['recipient_id'],
            ':type'          => $data['type'],
            ':title'         => $data['title'],
            ':message'       => $data['message'] ?? null,
            ':reference_type' => $data['reference_type'] ?? null,
            ':reference_id'  => $data['reference_id'] ?? null,
            ':channel'       => $data['channel'] ?? 'in_app',
            ':is_read'       => (int) ($data['is_read'] ?? false),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return Notification[]
     */
    public function unreadForUser(int $userId): array
    {
        $sql = 'SELECT * FROM notifications WHERE recipient_id = :recipient_id AND is_read = 0 ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':recipient_id' => $userId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return Notification[]
     */
    public function allForUser(int $userId): array
    {
        $sql = 'SELECT * FROM notifications WHERE recipient_id = :recipient_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':recipient_id' => $userId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function markRead(int $id): void
    {
        $sql = 'UPDATE notifications SET is_read = 1 WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function markAllRead(int $userId): void
    {
        $sql = 'UPDATE notifications SET is_read = 1 WHERE recipient_id = :recipient_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':recipient_id' => $userId]);
    }

    public function countUnread(int $userId): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM notifications WHERE recipient_id = :recipient_id AND is_read = 0';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':recipient_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    private function hydrate(array $row): Notification
    {
        return Notification::fromRow($row);
    }
}
