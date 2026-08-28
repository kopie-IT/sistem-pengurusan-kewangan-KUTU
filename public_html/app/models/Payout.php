<?php

declare(strict_types=1);

namespace App\Models;

final class Payout
{
    public function __construct(
        public int $id,
        public int $planId,
        public ?int $planCycleId,
        public ?int $payoutScheduleId,
        public int $recipientMemberId,
        public string $grossPayout,
        public string $actualCollection,
        public string $adminFee,
        public string $netPayout,
        public string $payoutMode,
        public string $status,
        public ?string $paymentReference,
        public ?int $paymentSlipId,
        public ?string $paidDate,
        public ?string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            planId: (int) $row['plan_id'],
            planCycleId: isset($row['plan_cycle_id']) ? (int) $row['plan_cycle_id'] : null,
            payoutScheduleId: isset($row['payout_schedule_id']) ? (int) $row['payout_schedule_id'] : null,
            recipientMemberId: (int) $row['recipient_member_id'],
            grossPayout: (string) $row['gross_payout'],
            actualCollection: (string) $row['actual_collection'],
            adminFee: (string) $row['admin_fee'],
            netPayout: (string) $row['net_payout'],
            payoutMode: $row['payout_mode'],
            status: $row['status'],
            paymentReference: $row['payment_reference'] ?? null,
            paymentSlipId: isset($row['payment_slip_id']) ? (int) $row['payment_slip_id'] : null,
            paidDate: $row['paid_date'] ?? null,
            createdAt: $row['created_at'] ?? null,
        );
    }
}
