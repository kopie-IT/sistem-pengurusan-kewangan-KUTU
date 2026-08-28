<?php

declare(strict_types=1);

namespace App\Models;

final class Plan
{
    public function __construct(
        public int $id,
        public string $planCode,
        public string $name,
        public ?string $description,
        public int $numberOfMembers,
        public string $contributionAmount,
        public string $paymentFrequency,
        public int $numberOfCycles,
        public ?string $startDate,
        public ?string $endDate,
        public string $status,
        public int $maxMembers,
        public int $minCreditScore,
        public bool $approvalRequired,
        public bool $allowMultiple,
        public bool $withdrawalAllowed,
        public string $payoutMode,
        public string $fixedPayoutAmount,
        public string $payoutFrequency,
        public ?int $payoutDay,
        public string $minScore,
        public ?int $createdBy,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            planCode: $row['plan_code'],
            name: $row['name'],
            description: $row['description'] ?? null,
            numberOfMembers: (int) $row['number_of_members'],
            contributionAmount: (string) $row['contribution_amount'],
            paymentFrequency: $row['payment_frequency'],
            numberOfCycles: (int) $row['number_of_cycles'],
            startDate: $row['start_date'] ?? null,
            endDate: $row['end_date'] ?? null,
            status: $row['status'],
            maxMembers: (int) $row['max_members'],
            minCreditScore: (int) $row['min_credit_score'],
            approvalRequired: (bool) $row['approval_required'],
            allowMultiple: (bool) $row['allow_multiple'],
            withdrawalAllowed: (bool) $row['withdrawal_allowed'],
            payoutMode: $row['payout_mode'],
            fixedPayoutAmount: (string) $row['fixed_payout_amount'],
            payoutFrequency: $row['payout_frequency'],
            payoutDay: isset($row['payout_day']) ? (int) $row['payout_day'] : null,
            minScore: (string) ($row['min_score'] ?? 0),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            createdAt: $row['created_at'],
        );
    }
}
