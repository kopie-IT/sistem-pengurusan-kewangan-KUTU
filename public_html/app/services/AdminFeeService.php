<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminFeeConfigRepository;
use App\Repositories\AdminFeeVersionRepository;

/**
 * Computes the admin fee for a payout and manages fee versioning.
 *
 * Fees are either a fixed amount or a percentage of the gross payout. A
 * config may be disabled for a plan, in which case the fee is always zero.
 * Percentage fees use BC MATH to avoid floating point errors (PRD section 57).
 */
final class AdminFeeService
{
    private const SCALE = 2;
    private const INTERNAL_SCALE = 4;

    public function __construct(
        private AdminFeeConfigRepository $configs,
        private AdminFeeVersionRepository $versions,
    ) {}

    /**
     * Compute the admin fee for a given plan and gross payout.
     *
     * @return array{ok: bool, fee: string, version_id: ?int, type: string, error?: string}
     */
    public function computeFee(int $planId, string $grossPayout, string $asOfDate): array
    {
        $config = $this->configs->findByPlan($planId);
        if ($config === null || !$config->enabled) {
            return ['ok' => true, 'fee' => '0.00', 'version_id' => null, 'type' => 'none'];
        }

        $version = $this->versions->findActiveForDate($config->id, $asOfDate);
        if ($version === null) {
            // No active version for the date — treat as zero fee to avoid surprises.
            return ['ok' => true, 'fee' => '0.00', 'version_id' => null, 'type' => $config->feeType];
        }

        $feeType = (string) $version['fee_type'];
        $feeValue = (string) $version['fee_value'];

        if ($feeType === 'fixed') {
            $fee = bcadd($feeValue, '0', self::SCALE);
        } else {
            // percentage: fee = gross * (value / 100)
            $rate = bcdiv($feeValue, '100', self::INTERNAL_SCALE);
            $fee = bcadd(bcmul($grossPayout, $rate, self::INTERNAL_SCALE), '0', self::SCALE);
        }

        return [
            'ok'         => true,
            'fee'        => $fee,
            'version_id' => (int) $version['id'],
            'type'       => $feeType,
        ];
    }

    /**
     * Create a new active fee version, superseding any previously active one.
     */
    public function ensureVersion(int $configId, string $feeType, string $feeValue, string $effectiveDate): int
    {
        $versionId = $this->versions->create([
            'admin_fee_config_id' => $configId,
            'fee_type'            => $feeType,
            'fee_value'           => $feeValue,
            'effective_date'      => $effectiveDate,
            'status'              => 'active',
        ]);

        $this->versions->supersede($configId, $versionId);

        return $versionId;
    }
}
