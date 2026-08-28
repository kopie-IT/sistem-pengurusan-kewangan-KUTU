<?php

declare(strict_types=1);

namespace App\Models;

final class PaymentBatch
{
    public function __construct(
        public int $id,
        public string $batchNo,
        public int $memberId,
        public string $totalAmount,
        public ?int $paymentSlipId,
        public string $status,
        public ?int $verifiedBy,
        public ?string $verifiedAt,
        public ?string $note,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            batchNo: $row['batch_no'],
            memberId: (int) $row['member_id'],
            totalAmount: (string) $row['total_amount'],
            paymentSlipId: isset($row['payment_slip_id']) ? (int) $row['payment_slip_id'] : null,
            status: $row['status'],
            verifiedBy: isset($row['verified_by']) ? (int) $row['verified_by'] : null,
            verifiedAt: $row['verified_at'] ?? null,
            note: $row['note'] ?? null,
            createdAt: $row['created_at'],
        );
    }
}
