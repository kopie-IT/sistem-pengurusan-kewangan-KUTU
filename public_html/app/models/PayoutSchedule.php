<?php

declare(strict_types=1);

namespace App\Models;

final class PayoutSchedule
{
    public function __construct(
        public int $id,
        public int $planId,
        public ?int $planCycleId,
        public int $recipientMemberId,
        public ?string $payoutDate,
        public string $expectedAmount,
        public string $status,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            planId: (int) $row['plan_id'],
            planCycleId: isset($row['plan_cycle_id']) ? (int) $row['plan_cycle_id'] : null,
            recipientMemberId: (int) $row['recipient_member_id'],
            payoutDate: $row['payout_date'] ?? null,
            expectedAmount: (string) $row['expected_amount'],
            status: $row['status'],
            createdAt: $row['created_at'],
        );
    }
}
