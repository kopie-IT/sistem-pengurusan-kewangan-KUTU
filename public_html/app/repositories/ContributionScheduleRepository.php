<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ContributionSchedule;
use PDO;

/**
 * Data access for the `contribution_schedules` table.
 */
final class ContributionScheduleRepository
{
    public function findById(int $id): ?ContributionSchedule
    {
        $sql = 'SELECT * FROM contribution_schedules WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return ContributionSchedule[]
     */
    public function allForMember(int $memberId): array
    {
        $sql = 'SELECT * FROM contribution_schedules WHERE member_id = :member_id ORDER BY due_date ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return ContributionSchedule[]
     */
    public function allForPlan(int $planId, ?string $status = null): array
    {
        $params = [':plan_id' => $planId];
        $where = '';
        if ($status !== null && $status !== '') {
            $where = 'AND status = :status';
            $params[':status'] = $status;
        }
        $sql = "SELECT * FROM contribution_schedules WHERE plan_id = :plan_id {$where} ORDER BY due_date ASC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return ContributionSchedule[]
     */
    public function allForPlanCycle(int $planCycleId): array
    {
        $sql = 'SELECT * FROM contribution_schedules WHERE plan_cycle_id = :plan_cycle_id ORDER BY due_date ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':plan_cycle_id' => $planCycleId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return ContributionSchedule[]
     */
    public function allOverdue(string $asOfDate): array
    {
        $sql = "SELECT * FROM contribution_schedules
                WHERE due_date < :as_of AND status IN ('pending', 'partial', 'overdue')
                ORDER BY due_date ASC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':as_of' => $asOfDate]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Dashboard-friendly listing of overdue or not-yet-paid schedules
     * joined to plan, member, and user info so the UI can render names
     * without further lookups. `status IN ('pending','partial','overdue')`
     * keeps rows that are still unpaid (regardless of whether a cron has
     * flipped the status to 'overdue'), and the `due_date <= :as_of`
     * constraint ensures future-dated schedules are excluded.
     *
     * @param int|null $memberId  Restrict to one member when provided.
     * @param int      $limit     Maximum rows returned (default 25).
     * @return array<int, array<string, mixed>>
     */
    public function findUnpaidWithDetails(?int $memberId, int $limit = 25): array
    {
        $params = [':as_of' => date('Y-m-d')];
        $where  = "cs.due_date <= :as_of
                    AND cs.status IN ('pending', 'partial', 'overdue')";
        if ($memberId !== null) {
            $where .= ' AND cs.member_id = :member_id';
            $params[':member_id'] = $memberId;
        }

        $sql = "SELECT cs.id, cs.plan_id, cs.member_id, cs.due_date,
                       cs.amount, cs.amount_paid, cs.status,
                       p.name        AS plan_name,
                       p.plan_code   AS plan_code,
                       m.member_code AS member_code,
                       u.name        AS member_name,
                       u.email       AS member_email
                  FROM contribution_schedules cs
            INNER JOIN plans   p ON p.id = cs.plan_id
            INNER JOIN members m ON m.id = cs.member_id
            INNER JOIN users   u ON u.id = m.user_id
                 WHERE {$where}
              ORDER BY cs.due_date ASC
                 LIMIT {$limit}";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Total outstanding amount + count of overdue/unpaid schedules.
     * Used to populate the dashboard summary tile.
     *
     * @return array{count:int, total:string}
     */
    public function unpaidSummary(?int $memberId): array
    {
        $params = [':as_of' => date('Y-m-d')];
        $where  = "due_date <= :as_of
                    AND status IN ('pending', 'partial', 'overdue')";
        if ($memberId !== null) {
            $where .= ' AND member_id = :member_id';
            $params[':member_id'] = $memberId;
        }

        $sql = "SELECT COUNT(*)        AS total_count,
                       COALESCE(SUM(amount - amount_paid), 0) AS outstanding
                  FROM contribution_schedules
                 WHERE {$where}";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'count' => (int) ($row['total_count'] ?? 0),
            'total' => number_format((float) ($row['outstanding'] ?? 0), 2, '.', ''),
        ];
    }

    public function findPendingForMemberPlan(int $memberId, int $planId): ?ContributionSchedule
    {
        $sql = "SELECT * FROM contribution_schedules
                WHERE member_id = :member_id AND plan_id = :plan_id
                  AND status IN ('pending', 'partial', 'overdue')
                ORDER BY due_date ASC
                LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId, ':plan_id' => $planId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO contribution_schedules (
                    plan_id, plan_cycle_id, member_id, due_date, amount, amount_paid, status
                ) VALUES (
                    :plan_id, :plan_cycle_id, :member_id, :due_date, :amount, :amount_paid, :status
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':plan_id'       => $data['plan_id'],
            ':plan_cycle_id' => $data['plan_cycle_id'] ?? null,
            ':member_id'     => $data['member_id'],
            ':due_date'      => $data['due_date'],
            ':amount'        => $data['amount'],
            ':amount_paid'   => $data['amount_paid'] ?? '0.00',
            ':status'        => $data['status'] ?? 'pending',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = ['plan_id', 'plan_cycle_id', 'member_id', 'due_date', 'amount', 'amount_paid', 'status'];
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
        $sql = 'UPDATE contribution_schedules SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status): void
    {
        $sql = 'UPDATE contribution_schedules SET status = :status, updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function markPaid(int $id, string $amountPaid, string $status): void
    {
        $sql = 'UPDATE contribution_schedules
                SET amount_paid = :amount_paid, status = :status, updated_at = NOW()
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':amount_paid' => $amountPaid,
            ':status'      => $status,
            ':id'          => $id,
        ]);
    }

    private function hydrate(array $row): ContributionSchedule
    {
        return ContributionSchedule::fromRow($row);
    }
}
