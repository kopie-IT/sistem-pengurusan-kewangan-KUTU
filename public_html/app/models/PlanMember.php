<?php

declare(strict_types=1);

namespace App\Models;

final class PlanMember
{
    public function __construct(
        public int $id,
        public int $planId,
        public int $memberId,
        public string $status,
        public ?string $joinedAt,
        public ?int $approvedBy,
        public ?string $withdrawalAt,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            planId: (int) $row['plan_id'],
            memberId: (int) $row['member_id'],
            status: $row['status'],
            joinedAt: $row['joined_at'] ?? null,
            approvedBy: isset($row['approved_by']) ? (int) $row['approved_by'] : null,
            withdrawalAt: $row['withdrawal_at'] ?? null,
            createdAt: $row['created_at'],
        );
    }
}
