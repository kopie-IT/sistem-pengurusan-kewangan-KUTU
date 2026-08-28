<?php

declare(strict_types=1);

namespace App\Models;

final class Notification
{
    public function __construct(
        public int $id,
        public int $recipientId,
        public string $type,
        public string $title,
        public ?string $message,
        public ?string $referenceType,
        public ?int $referenceId,
        public string $channel,
        public bool $isRead,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            recipientId: (int) $row['recipient_id'],
            type: $row['type'],
            title: $row['title'],
            message: $row['message'] ?? null,
            referenceType: $row['reference_type'] ?? null,
            referenceId: isset($row['reference_id']) ? (int) $row['reference_id'] : null,
            channel: $row['channel'],
            isRead: (bool) $row['is_read'],
            createdAt: $row['created_at'],
        );
    }
}
