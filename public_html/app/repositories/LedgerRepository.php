<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Data access for the `ledger_transactions` table. Rows are returned as arrays.
 */
final class LedgerRepository
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO ledger_transactions (
                    transaction_type, member_id, plan_id, reference_id, amount, currency, description
                ) VALUES (
                    :transaction_type, :member_id, :plan_id, :reference_id, :amount, :currency, :description
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':transaction_type' => $data['transaction_type'],
            ':member_id'        => $data['member_id'] ?? null,
            ':plan_id'          => $data['plan_id'] ?? null,
            ':reference_id'     => $data['reference_id'] ?? null,
            ':amount'           => $data['amount'],
            ':currency'         => $data['currency'] ?? 'MYR',
            ':description'      => $data['description'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return array[]
     */
    public function allForMember(int $memberId): array
    {
        $sql = 'SELECT * FROM ledger_transactions WHERE member_id = :member_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array[]
     */
    public function allForPlan(int $planId): array
    {
        $sql = 'SELECT * FROM ledger_transactions WHERE plan_id = :plan_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':plan_id' => $planId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array[]
     */
    public function recent(int $limit = 50): array
    {
        $sql = 'SELECT * FROM ledger_transactions ORDER BY id DESC LIMIT :limit';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':limit' => $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, string>  Sum of amounts grouped by transaction_type.
     */
    public function balanceSummary(): array
    {
        $sql = 'SELECT transaction_type, SUM(amount) AS total
                FROM ledger_transactions
                GROUP BY transaction_type';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['transaction_type']] = (string) $row['total'];
        }
        return $result;
    }
}
