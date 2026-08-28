<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Data access for the `withdrawal_requests` table. Rows are returned as arrays.
 */
final class WithdrawalRepository
{
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM withdrawal_requests WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @return array[]
     */
    public function allForMember(int $memberId): array
    {
        $sql = 'SELECT * FROM withdrawal_requests WHERE member_id = :member_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array[]
     */
    public function allPending(?string $search = null): array
    {
        $params = [':status' => 'pending'];
        $where = '';
        if ($search !== null && $search !== '') {
            $where = 'AND (reason LIKE :search OR notes LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        $sql = "SELECT wr.* FROM withdrawal_requests wr
                WHERE wr.status = :status {$where}
                ORDER BY wr.id DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO withdrawal_requests (
                    member_id, plan_id, reason, request_date, current_cycle, outstanding,
                    score_impact, status
                ) VALUES (
                    :member_id, :plan_id, :reason, :request_date, :current_cycle, :outstanding,
                    :score_impact, :status
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':member_id'     => $data['member_id'],
            ':plan_id'       => $data['plan_id'],
            ':reason'        => $data['reason'] ?? null,
            ':request_date'  => $data['request_date'] ?? null,
            ':current_cycle' => $data['current_cycle'] ?? null,
            ':outstanding'   => $data['outstanding'] ?? null,
            ':score_impact'  => $data['score_impact'] ?? null,
            ':status'        => $data['status'] ?? 'pending',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = [
            'member_id', 'plan_id', 'reason', 'request_date', 'current_cycle',
            'outstanding', 'score_impact', 'status', 'approved_by', 'decision_date', 'notes',
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
        $sql = 'UPDATE withdrawal_requests SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status, ?int $approvedBy = null): void
    {
        $sql = 'UPDATE withdrawal_requests
                SET status = :status,
                    approved_by = :approved_by,
                    decision_date = IF(:approved_by IS NOT NULL, NOW(), decision_date),
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':status'      => $status,
            ':approved_by' => $approvedBy,
            ':id'          => $id,
        ]);
    }
}
