<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NotificationRepository;

/**
 * Handles creation and management of in-app notifications.
 *
 * Notifications are persisted through NotificationRepository and surfaced to
 * users in the UI. Malformed reference data is normalised so a single broken
 * notification never crashes the calling service.
 */
final class NotificationService
{
    public function __construct(
        private NotificationRepository $notifications,
    ) {}

    /**
     * Create a single in-app notification.
     *
     * @param array<string, mixed>|null $reference
     */
    public function notify(int $userId, string $type, string $title, string $message, ?array $reference = []): int
    {
        $refType = null;
        $refId = null;
        if (!empty($reference)) {
            $refType = $reference['type'] ?? null;
            $refId = isset($reference['id']) ? (int) $reference['id'] : null;
        }

        return $this->notifications->create([
            'recipient_id'   => $userId,
            'type'           => $type,
            'title'          => $title,
            'message'        => $message,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'channel'        => 'in_app',
            'is_read'        => false,
        ]);
    }

    /**
     * Create the same notification for many users.
     *
     * @param int[]                  $userIds
     * @param array<string, mixed>|null $reference
     */
    public function notifyMany(array $userIds, string $type, string $title, string $message, ?array $reference = []): array
    {
        $ids = [];
        foreach ($userIds as $userId) {
            $ids[] = $this->notify((int) $userId, $type, $title, $message, $reference);
        }
        return $ids;
    }

    /**
     * @return \App\Models\Notification[]
     */
    public function unread(int $userId): array
    {
        return $this->notifications->unreadForUser($userId);
    }

    /**
     * @return \App\Models\Notification[]
     */
    public function all(int $userId): array
    {
        return $this->notifications->allForUser($userId);
    }

    public function markRead(int $id): void
    {
        $this->notifications->markRead($id);
    }

    public function markAllRead(int $userId): void
    {
        $this->notifications->markAllRead($userId);
    }

    public function count(int $userId): int
    {
        return $this->notifications->countUnread($userId);
    }
}
