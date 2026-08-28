<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LedgerRepository;

/**
 * Thin wrapper around ledger transaction persistence.
 *
 * All financial movements (contributions, payouts, fees, shortfalls, refunds
 * and manual adjustments) are recorded as ledger_transactions. This service
 * keeps controllers free of direct ledger writes.
 */
final class LedgerService
{
    public function __construct(
        private LedgerRepository $ledger,
    ) {}

    /**
     * Record a contribution received from a member.
     */
    public function recordContribution(int $memberId, int $planId, string $amount, ?int $referenceId = null, ?string $description = null): int
    {
        return $this->ledger->create([
            'transaction_type' => 'contribution',
            'member_id'        => $memberId,
            'plan_id'          => $planId,
            'reference_id'     => $referenceId,
            'amount'           => $amount,
            'description'      => $description ?? 'Contribution',
        ]);
    }

    /**
     * Record a payout disbursed to a member (net amount).
     */
    public function recordPayout(int $memberId, int $planId, string $amount, ?int $referenceId = null, ?string $description = null): int
    {
        return $this->ledger->create([
            'transaction_type' => 'payout',
            'member_id'        => $memberId,
            'plan_id'          => $planId,
            'reference_id'     => $referenceId,
            'amount'           => $amount,
            'description'      => $description ?? 'Payout',
        ]);
    }

    /**
     * Record an admin fee collected.
     */
    public function recordAdminFee(int $memberId, int $planId, string $amount, ?int $referenceId = null, ?string $description = null): int
    {
        return $this->ledger->create([
            'transaction_type' => 'admin_fee',
            'member_id'        => $memberId,
            'plan_id'          => $planId,
            'reference_id'     => $referenceId,
            'amount'           => $amount,
            'description'      => $description ?? 'Admin fee',
        ]);
    }

    /**
     * Record a shortfall (collection below expected payout).
     */
    public function recordShortfall(int $memberId, int $planId, string $amount, ?int $referenceId = null, ?string $description = null): int
    {
        return $this->ledger->create([
            'transaction_type' => 'shortfall',
            'member_id'        => $memberId,
            'plan_id'          => $planId,
            'reference_id'     => $referenceId,
            'amount'           => $amount,
            'description'      => $description ?? 'Shortfall',
        ]);
    }

    /**
     * Record a refund to a member.
     */
    public function recordRefund(int $memberId, int $planId, string $amount, ?int $referenceId = null, ?string $description = null): int
    {
        return $this->ledger->create([
            'transaction_type' => 'refund',
            'member_id'        => $memberId,
            'plan_id'          => $planId,
            'reference_id'     => $referenceId,
            'amount'           => $amount,
            'description'      => $description ?? 'Refund',
        ]);
    }

    /**
     * Record a manual adjustment.
     */
    public function recordAdjustment(int $memberId, int $planId, string $amount, ?int $referenceId = null, ?string $description = null): int
    {
        return $this->ledger->create([
            'transaction_type' => 'adjustment',
            'member_id'        => $memberId,
            'plan_id'          => $planId,
            'reference_id'     => $referenceId,
            'amount'           => $amount,
            'description'      => $description ?? 'Adjustment',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function summary(): array
    {
        return $this->ledger->balanceSummary();
    }
}
