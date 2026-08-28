<?php

declare(strict_types=1);

namespace App\Models;

final class ContributionSchedule
{
    public function __construct(
        public int $id,
        public int $planId,
        public ?int $planCycleId,
        public int $memberId,
        public string $dueDate,
        public string $amount,
        public string $amountPaid,
        public string $status,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            planId: (int) $row['plan_id'],
            planCycleId: isset($row['plan_cycle_id']) ? (int) $row['plan_cycle_id'] : null,
            memberId: (int) $row['member_id'],
            dueDate: $row['due_date'],
            amount: (string) $row['amount'],
            amountPaid: (string) $row['amount_paid'],
            status: $row['status'],
            createdAt: $row['created_at'],
        );
    }
}
