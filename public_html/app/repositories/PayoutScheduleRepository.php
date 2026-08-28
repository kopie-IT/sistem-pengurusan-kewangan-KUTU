<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\PayoutSchedule;
use PDO;

/**
 * Data access for the `payout_schedules` table.
 */
final class PayoutScheduleRepository
{
    public function findById(int $id): ?PayoutSchedule
    {
        $sql = 'SELECT * FROM payout_schedules WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return PayoutSchedule[]
     */
    public function allForPlan(int $planId, ?string $status = null): array
    {
        $params = [':plan_id' => $planId];
        $where = '';
        if ($status !== null && $status !== '') {
            $where = 'AND status = :status';
            $params[':status'] = $status;
        }
        $sql = "SELECT * FROM payout_schedules WHERE plan_id = :plan_id {$where} ORDER BY payout_date ASC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return PayoutSchedule[]
     */
    public function allUpcomingForMember(int $memberId): array
    {
        $sql = "SELECT ps.* FROM payout_schedules ps
                INNER JOIN plan_members pm ON pm.plan_id = ps.plan_id
                WHERE pm.member_id = :member_id
                  AND ps.recipient_member_id = pm.member_id
                  AND ps.status IN ('scheduled', 'due', 'processing')
                ORDER BY ps.payout_date ASC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO payout_schedules (
                    plan_id, plan_cycle_id, recipient_member_id, payout_date, expected_amount, status
                ) VALUES (
                    :plan_id, :plan_cycle_id, :recipient_member_id, :payout_date, :expected_amount, :status
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':plan_id'           => $data['plan_id'],
            ':plan_cycle_id'     => $data['plan_cycle_id'] ?? null,
            ':recipient_member_id' => $data['recipient_member_id'],
            ':payout_date'        => $data['payout_date'] ?? null,
            ':expected_amount'    => $data['expected_amount'],
            ':status'             => $data['status'] ?? 'scheduled',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = ['plan_id', 'plan_cycle_id', 'recipient_member_id', 'payout_date', 'expected_amount', 'status'];
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
        $sql = 'UPDATE payout_schedules SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status): void
    {
        $sql = 'UPDATE payout_schedules SET status = :status, updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    private function hydrate(array $row): PayoutSchedule
    {
        return PayoutSchedule::fromRow($row);
    }
}
