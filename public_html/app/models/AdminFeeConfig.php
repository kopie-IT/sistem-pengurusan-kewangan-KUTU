<?php

declare(strict_types=1);

namespace App\Models;

final class AdminFeeConfig
{
    public function __construct(
        public int $id,
        public int $planId,
        public bool $enabled,
        public string $feeType,
        public string $feeValue,
        public string $status,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            planId: (int) $row['plan_id'],
            enabled: (bool) $row['enabled'],
            feeType: $row['fee_type'],
            feeValue: (string) $row['fee_value'],
            status: $row['status'],
            createdAt: $row['created_at'],
        );
    }
}
