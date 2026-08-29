<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Payout;
use PDO;

/**
 * Data access for the `payouts` table.
 */
final class PayoutRepository
{
    public function findById(int $id): ?Payout
    {
        $sql = 'SELECT * FROM payouts WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return Payout[]
     */
    public function allForPlan(int $planId, ?string $status = null): array
    {
        $params = [':plan_id' => $planId];
        $where = '';
        if ($status !== null && $status !== '') {
            $where = 'AND status = :status';
            $params[':status'] = $status;
        }
        $sql = "SELECT * FROM payouts WHERE plan_id = :plan_id {$where} ORDER BY id DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return Payout[]
     */
    public function allForRecipient(int $recipientMemberId): array
    {
        $sql = 'SELECT * FROM payouts WHERE recipient_member_id = :recipient_member_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':recipient_member_id' => $recipientMemberId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return Payout[]
     */
    public function allUpcomingForMember(int $memberId): array
    {
        $sql = "SELECT p.* FROM payouts p
                INNER JOIN plan_members pm ON pm.plan_id = p.plan_id
                WHERE pm.member_id = :member_id
                  AND p.recipient_member_id = pm.member_id
                  AND p.status IN ('scheduled', 'due', 'processing')
                ORDER BY p.paid_date ASC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO payouts (
                    plan_id, plan_cycle_id, payout_schedule_id, recipient_member_id,
                    gross_payout, actual_collection, admin_fee, net_payout, payout_mode,
                    admin_fee_version_id, shortfall_amount, shortfall_id, status,
                    payment_reference, payment_slip_id
                ) VALUES (
                    :plan_id, :plan_cycle_id, :payout_schedule_id, :recipient_member_id,
                    :gross_payout, :actual_collection, :admin_fee, :net_payout, :payout_mode,
                    :admin_fee_version_id, :shortfall_amount, :shortfall_id, :status,
                    :payment_reference, :payment_slip_id
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':plan_id'             => $data['plan_id'],
            ':plan_cycle_id'       => $data['plan_cycle_id'] ?? null,
            ':payout_schedule_id'  => $data['payout_schedule_id'] ?? null,
            ':recipient_member_id' => $data['recipient_member_id'],
            ':gross_payout'        => $data['gross_payout'],
            ':actual_collection'   => $data['actual_collection'] ?? '0.00',
            ':admin_fee'           => $data['admin_fee'] ?? '0.00',
            ':net_payout'          => $data['net_payout'],
            ':payout_mode'         => $data['payout_mode'] ?? 'fixed',
            ':admin_fee_version_id' => $data['admin_fee_version_id'] ?? null,
            ':shortfall_amount'    => $data['shortfall_amount'] ?? '0.00',
            ':shortfall_id'        => $data['shortfall_id'] ?? null,
            ':status'              => $data['status'] ?? 'scheduled',
            ':payment_reference'   => $data['payment_reference'] ?? null,
            ':payment_slip_id'     => $data['payment_slip_id'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = [
            'plan_id', 'plan_cycle_id', 'payout_schedule_id', 'recipient_member_id',
            'gross_payout', 'actual_collection', 'admin_fee', 'net_payout', 'payout_mode',
            'admin_fee_version_id', 'shortfall_amount', 'shortfall_id', 'status',
            'payment_reference', 'payment_slip_id',
        ];
        $fields = [];
        $params = [':id' => $id];
        foreach ($columns as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "`{$col}` = :{$col}";
                $params[":{$col}"] = $data[$col];
            }
        }
        if ($fields === []) {
            return;
        }
        $sql = 'UPDATE payouts SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function updateStatus(
        int $id,
        string $status,
        ?int $paidBy = null,
        ?string $paidDate = null
    ): void {
        $sql = 'UPDATE payouts
                SET status = :status,
                    paid_by = :paid_by,
                    paid_date = IF(:paid_date_check IS NOT NULL, :paid_date_value, paid_date),
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':status'          => $status,
            ':paid_by'         => $paidBy,
            ':paid_date_check' => $paidDate,
            ':paid_date_value' => $paidDate,
            ':id'              => $id,
        ]);
    }

    /**
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $sql = 'SELECT status, COUNT(*) AS total FROM payouts GROUP BY status';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['status']] = (int) $row['total'];
        }
        return $result;
    }

    private function hydrate(array $row): Payout
    {
        return Payout::fromRow($row);
    }
}
