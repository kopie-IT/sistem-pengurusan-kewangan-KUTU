<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Data access for the `shortfalls` table. Rows are returned as arrays.
 */
final class ShortfallRepository
{
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM shortfalls WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @return array[]
     */
    public function all(?string $status = null): array
    {
        $params = [];
        $where = '';
        if ($status !== null && $status !== '') {
            $where = 'WHERE status = :status';
            $params[':status'] = $status;
        }
        $sql = "SELECT * FROM shortfalls {$where} ORDER BY id DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO shortfalls (
                    plan_id, plan_cycle_id, payout_id, expected_amount, actual_collection,
                    shortfall_amount, status
                ) VALUES (
                    :plan_id, :plan_cycle_id, :payout_id, :expected_amount, :actual_collection,
                    :shortfall_amount, :status
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':plan_id'           => $data['plan_id'],
            ':plan_cycle_id'     => $data['plan_cycle_id'] ?? null,
            ':payout_id'         => $data['payout_id'] ?? null,
            ':expected_amount'   => $data['expected_amount'],
            ':actual_collection' => $data['actual_collection'],
            ':shortfall_amount'  => $data['shortfall_amount'],
            ':status'            => $data['status'] ?? 'open',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = [
            'plan_id', 'plan_cycle_id', 'payout_id', 'expected_amount', 'actual_collection',
            'shortfall_amount', 'status', 'resolution', 'notes', 'resolved_at', 'approved_by',
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
        $sql = 'UPDATE shortfalls SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status, ?int $approvedBy = null): void
    {
        $sql = 'UPDATE shortfalls
                SET status = :status,
                    approved_by = :approved_by,
                    resolved_at = IF(:approved_by IS NOT NULL, NOW(), resolved_at)
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':status'      => $status,
            ':approved_by' => $approvedBy,
            ':id'          => $id,
        ]);
    }
}
