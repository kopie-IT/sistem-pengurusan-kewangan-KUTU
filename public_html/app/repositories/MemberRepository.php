<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Member;
use PDO;

/**
 * Data access for the `members` table (joined with `users`).
 */
final class MemberRepository
{
    public function findById(int $id): ?Member
    {
        $sql = 'SELECT m.*, u.name AS name, u.email AS email
                FROM members m
                LEFT JOIN users u ON u.id = m.user_id
                WHERE m.id = :id
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function findByUserId(int $userId): ?Member
    {
        $sql = 'SELECT m.*, u.name AS name, u.email AS email
                FROM members m
                LEFT JOIN users u ON u.id = m.user_id
                WHERE m.user_id = :user_id
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return Member[]
     */
    public function all(int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        $params = [':limit' => $limit, ':offset' => $offset];
        $where = '';
        if ($search !== null && $search !== '') {
            $where = 'WHERE m.member_code LIKE :search OR u.name LIKE :search OR u.email LIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        $sql = "SELECT m.*, u.name AS name, u.email AS email
                FROM members m
                LEFT JOIN users u ON u.id = m.user_id
                {$where}
                ORDER BY m.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return Member[]
     */
    public function allActive(int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT m.*, u.name AS name, u.email AS email
                FROM members m
                LEFT JOIN users u ON u.id = m.user_id
                WHERE m.status = :status
                ORDER BY m.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':status' => 'active', ':limit' => $limit, ':offset' => $offset]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO members (user_id, member_code, phone, ic_number, address, credit_score, status)
                VALUES (:user_id, :member_code, :phone, :ic_number, :address, :credit_score, :status)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':user_id'     => $data['user_id'],
            ':member_code' => $data['member_code'] ?? null,
            ':phone'       => $data['phone'] ?? null,
            ':ic_number'   => $data['ic_number'] ?? null,
            ':address'     => $data['address'] ?? null,
            ':credit_score' => $data['credit_score'] ?? 100,
            ':status'      => $data['status'] ?? 'active',
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = [':id' => $id];
        foreach (['user_id', 'member_code', 'phone', 'ic_number', 'address', 'credit_score', 'status'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "`{$col}` = :{$col}";
                $params[":{$col}"] = $data[$col];
            }
        }
        if ($fields === []) {
            return;
        }
        $sql = 'UPDATE members SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function findByMemberCode(string $memberCode): ?Member
    {
        $sql = 'SELECT m.*, u.name AS name, u.email AS email
                FROM members m
                LEFT JOIN users u ON u.id = m.user_id
                WHERE m.member_code = :member_code
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_code' => $memberCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM members';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function hydrate(array $row): Member
    {
        return Member::fromRow($row);
    }
}
