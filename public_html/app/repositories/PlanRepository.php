<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Plan;
use PDO;

/**
 * Data access for the `plans` table.
 */
final class PlanRepository
{
    public function findById(int $id): ?Plan
    {
        $sql = 'SELECT * FROM plans WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return Plan[]
     */
    public function all(?string $status = null, ?string $search = null): array
    {
        $params = [];
        $where = [];
        if ($status !== null && $status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(plan_code LIKE :search OR name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        $sql = 'SELECT * FROM plans'
            . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return Plan[]
     */
    public function allOpen(): array
    {
        $sql = "SELECT * FROM plans WHERE status IN ('open', 'active') ORDER BY id DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO plans (
                    plan_code, name, description, number_of_members, contribution_amount,
                    payment_frequency, number_of_cycles, start_date, end_date, status,
                    max_members, min_credit_score, approval_required, allow_multiple,
                    withdrawal_allowed, payout_mode, fixed_payout_amount, payout_frequency,
                    payout_day, min_score, created_by
                ) VALUES (
                    :plan_code, :name, :description, :number_of_members, :contribution_amount,
                    :payment_frequency, :number_of_cycles, :start_date, :end_date, :status,
                    :max_members, :min_credit_score, :approval_required, :allow_multiple,
                    :withdrawal_allowed, :payout_mode, :fixed_payout_amount, :payout_frequency,
                    :payout_day, :min_score, :created_by
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':plan_code'           => $data['plan_code'],
            ':name'                => $data['name'],
            ':description'         => $data['description'] ?? null,
            ':number_of_members'   => $data['number_of_members'] ?? 0,
            ':contribution_amount' => $data['contribution_amount'],
            ':payment_frequency'   => $data['payment_frequency'] ?? 'monthly',
            ':number_of_cycles'    => $data['number_of_cycles'] ?? 1,
            ':start_date'          => $data['start_date'] ?? null,
            ':end_date'            => $data['end_date'] ?? null,
            ':status'              => $data['status'] ?? 'draft',
            ':max_members'         => $data['max_members'] ?? 0,
            ':min_credit_score'    => $data['min_credit_score'] ?? 0,
            ':approval_required'    => $data['approval_required'] ?? false,
            ':allow_multiple'      => $data['allow_multiple'] ?? true,
            ':withdrawal_allowed'   => $data['withdrawal_allowed'] ?? false,
            ':payout_mode'         => $data['payout_mode'] ?? 'fixed',
            ':fixed_payout_amount' => $data['fixed_payout_amount'],
            ':payout_frequency'    => $data['payout_frequency'] ?? 'monthly',
            ':payout_day'          => $data['payout_day'] ?? null,
            ':min_score'           => $data['min_score'] ?? 0,
            ':created_by'          => $data['created_by'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = [
            'plan_code', 'name', 'description', 'number_of_members', 'contribution_amount',
            'payment_frequency', 'number_of_cycles', 'start_date', 'end_date', 'status',
            'max_members', 'min_credit_score', 'approval_required', 'allow_multiple',
            'withdrawal_allowed', 'payout_mode', 'fixed_payout_amount', 'payout_frequency',
            'payout_day', 'min_score', 'created_by',
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
        $sql = 'UPDATE plans SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $sql = 'SELECT status, COUNT(*) AS total FROM plans GROUP BY status';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['status']] = (int) $row['total'];
        }
        return $result;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM plans';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function hydrate(array $row): Plan
    {
        return Plan::fromRow($row);
    }
}
