<?php

declare(strict_types=1);

namespace App\Models;

final class CreditScore
{
    public function __construct(
        public int $id,
        public int $memberId,
        public int $score,
        public string $level,
        public string $updatedAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            memberId: (int) $row['member_id'],
            score: (int) $row['score'],
            level: $row['level'],
            updatedAt: $row['updated_at'],
        );
    }
}
