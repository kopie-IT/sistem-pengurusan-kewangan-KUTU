<?php

declare(strict_types=1);

namespace App\Models;

final class PaymentSlip
{
    public function __construct(
        public int $id,
        public ?int $memberId,
        public string $storedName,
        public ?string $originalName,
        public ?string $mimeType,
        public int $sizeBytes,
        public string $purpose,
        public ?int $uploadedBy,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            memberId: isset($row['member_id']) ? (int) $row['member_id'] : null,
            storedName: $row['stored_name'],
            originalName: $row['original_name'] ?? null,
            mimeType: $row['mime_type'] ?? null,
            sizeBytes: (int) $row['size_bytes'],
            purpose: $row['purpose'],
            uploadedBy: isset($row['uploaded_by']) ? (int) $row['uploaded_by'] : null,
            createdAt: $row['created_at'],
        );
    }
}
