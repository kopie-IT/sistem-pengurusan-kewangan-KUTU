<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Data access for the `admin_fee_versions` table.
 */
final class AdminFeeVersionRepository
{
    public function findActiveForDate(int $configId, string $date): ?array
    {
        $sql = "SELECT * FROM admin_fee_versions
                WHERE admin_fee_config_id = :config_id
                  AND status = 'active'
                  AND effective_date <= :date
                ORDER BY effective_date DESC
                LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':config_id' => $configId, ':date' => $date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO admin_fee_versions (
                    admin_fee_config_id, fee_type, fee_value, effective_date, status
                ) VALUES (
                    :admin_fee_config_id, :fee_type, :fee_value, :effective_date, :status
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':admin_fee_config_id' => $data['admin_fee_config_id'],
            ':fee_type'            => $data['fee_type'] ?? 'fixed',
            ':fee_value'           => $data['fee_value'],
            ':effective_date'      => $data['effective_date'],
            ':status'              => $data['status'] ?? 'active',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function supersede(int $configId, int $exceptVersionId): void
    {
        $sql = "UPDATE admin_fee_versions
                SET status = 'superseded'
                WHERE admin_fee_config_id = :config_id
                  AND id != :except_version_id
                  AND status = 'active'";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':config_id'         => $configId,
            ':except_version_id' => $exceptVersionId,
        ]);
    }
}
