<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AdminFeeConfig;
use PDO;

/**
 * Data access for the `admin_fee_configs` table.
 */
final class AdminFeeConfigRepository
{
    public function findByPlan(int $planId): ?AdminFeeConfig
    {
        $sql = 'SELECT * FROM admin_fee_configs WHERE plan_id = :plan_id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':plan_id' => $planId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO admin_fee_configs (plan_id, enabled, fee_type, fee_value, status)
                VALUES (:plan_id, :enabled, :fee_type, :fee_value, :status)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':plan_id'   => $data['plan_id'],
            ':enabled'   => $data['enabled'] ?? true,
            ':fee_type'  => $data['fee_type'] ?? 'fixed',
            ':fee_value' => $data['fee_value'],
            ':status'    => $data['status'] ?? 'active',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = ['plan_id', 'enabled', 'fee_type', 'fee_value', 'status'];
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
        $sql = 'UPDATE admin_fee_configs SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    private function hydrate(array $row): AdminFeeConfig
    {
        return AdminFeeConfig::fromRow($row);
    }
}
