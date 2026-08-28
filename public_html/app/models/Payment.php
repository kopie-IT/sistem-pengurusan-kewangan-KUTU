<?php

declare(strict_types=1);

namespace App\Models;

final class Payment
{
    public function __construct(
        public int $id,
        public int $memberId,
        public int $planId,
        public ?int $contributionScheduleId,
        public ?int $batchId,
        public string $amount,
        public string $status,
        public ?int $paymentSlipId,
        public ?int $verifiedBy,
        public ?string $verifiedAt,
        public ?string $note,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            memberId: (int) $row['member_id'],
            planId: (int) $row['plan_id'],
            contributionScheduleId: isset($row['contribution_schedule_id']) ? (int) $row['contribution_schedule_id'] : null,
            batchId: isset($row['batch_id']) ? (int) $row['batch_id'] : null,
            amount: (string) $row['amount'],
            status: $row['status'],
            paymentSlipId: isset($row['payment_slip_id']) ? (int) $row['payment_slip_id'] : null,
            verifiedBy: isset($row['verified_by']) ? (int) $row['verified_by'] : null,
            verifiedAt: $row['verified_at'] ?? null,
            note: $row['note'] ?? null,
            createdAt: $row['created_at'],
        );
    }
}
