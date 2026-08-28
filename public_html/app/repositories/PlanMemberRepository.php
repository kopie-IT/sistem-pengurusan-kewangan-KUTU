<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\PlanMember;
use PDO;

/**
 * Data access for the `plan_members` table.
 */
final class PlanMemberRepository
{
    public function findById(int $id): ?PlanMember
    {
        $sql = 'SELECT * FROM plan_members WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function findByPlanAndMember(int $planId, int $memberId): ?PlanMember
    {
        $sql = 'SELECT * FROM plan_members WHERE plan_id = :plan_id AND member_id = :member_id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':plan_id' => $planId, ':member_id' => $memberId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return PlanMember[]
     */
    public function allForMember(int $memberId): array
    {
        $sql = 'SELECT * FROM plan_members WHERE member_id = :member_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return PlanMember[]
     */
    public function allForPlan(int $planId, ?string $status = null): array
    {
        $params = [':plan_id' => $planId];
        $where = '';
        if ($status !== null && $status !== '') {
            $where = 'AND status = :status';
            $params[':status'] = $status;
        }
        $sql = "SELECT * FROM plan_members WHERE plan_id = :plan_id {$where} ORDER BY id DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return PlanMember[]
     */
    public function allActiveForMember(int $memberId): array
    {
        $sql = "SELECT * FROM plan_members WHERE member_id = :member_id AND status = 'active' ORDER BY id DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO plan_members (plan_id, member_id, status, joined_at, approved_by)
                VALUES (:plan_id, :member_id, :status, :joined_at, :approved_by)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':plan_id'    => $data['plan_id'],
            ':member_id'  => $data['member_id'],
            ':status'     => $data['status'] ?? 'pending',
            ':joined_at'  => $data['joined_at'] ?? null,
            ':approved_by' => $data['approved_by'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = ['plan_id', 'member_id', 'status', 'joined_at', 'approved_by', 'withdrawal_at'];
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
        $sql = 'UPDATE plan_members SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function countActiveForPlan(int $planId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM plan_members WHERE plan_id = :plan_id AND status = 'active'";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':plan_id' => $planId]);
        return (int) $stmt->fetchColumn();
    }

    public function countForPlan(int $planId): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM plan_members WHERE plan_id = :plan_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':plan_id' => $planId]);
        return (int) $stmt->fetchColumn();
    }

    private function hydrate(array $row): PlanMember
    {
        return PlanMember::fromRow($row);
    }
}
