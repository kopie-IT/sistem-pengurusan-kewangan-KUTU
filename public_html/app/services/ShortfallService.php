<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\LedgerRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\ShortfallRepository;

/**
 * Records and resolves payout shortfalls (collection below the expected/fixed
 * payout). Shortfall rows are surfaced in the admin dashboard and can be
 * resolved with a documented resolution and approver.
 */
final class ShortfallService
{
    public function __construct(
        private ShortfallRepository $shortfalls,
        private PayoutRepository $payouts,
        private LedgerRepository $ledger,
        private NotificationRepository $notifications,
    ) {}

    /**
     * Record a new shortfall row.
     *
     * @return int The new shortfall id.
     */
    public function record(int $planId, ?int $planCycleId, ?int $payoutId, string $expected, string $actual, string $shortfall): int
    {
        $id = $this->shortfalls->create([
            'plan_id'           => $planId,
            'plan_cycle_id'     => $planCycleId,
            'payout_id'         => $payoutId,
            'expected_amount'   => $expected,
            'actual_collection' => $actual,
            'shortfall_amount'  => $shortfall,
            'status'            => 'open',
        ]);

        AuditService::log('shortfall.create', null, 'shortfall', $id, [
            'plan_id'   => $planId,
            'payout_id' => $payoutId,
            'shortfall' => $shortfall,
        ]);

        return $id;
    }

    /**
     * Resolve an open shortfall.
     *
     * @return array{ok: bool, error?: string}
     */
    public function resolve(int $shortfallId, string $resolution, ?string $notes, int $actorId): array
    {
        $shortfall = $this->shortfalls->findById($shortfallId);
        if ($shortfall === null) {
            return ['ok' => false, 'error' => 'Rekod kekurangan tidak dijumpai.'];
        }

        $this->shortfalls->update($shortfallId, [
            'status'      => 'resolved',
            'resolution'  => $resolution,
            'notes'       => $notes,
            'resolved_at' => date('Y-m-d H:i:s'),
            'approved_by' => $actorId,
        ]);

        AuditService::log('shortfall.resolve', $actorId, 'shortfall', $shortfallId, [
            'resolution' => $resolution,
        ]);

        return ['ok' => true];
    }

    /**
     * @return array[] Shortfall rows (optionally filtered by status).
     */
    public function list(?string $status = null): array
    {
        return $this->shortfalls->all($status);
    }
}
