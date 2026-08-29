<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\PaymentBatch;
use PDO;

/**
 * Data access for the `payment_batches` table.
 */
final class PaymentBatchRepository
{
    public function findById(int $id): ?PaymentBatch
    {
        $sql = 'SELECT * FROM payment_batches WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return PaymentBatch[]
     */
    public function allForMember(int $memberId): array
    {
        $sql = 'SELECT * FROM payment_batches WHERE member_id = :member_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return PaymentBatch[]
     */
    public function allPendingVerification(?string $search = null): array
    {
        $params = [':status' => 'pending_verification'];
        $where = '';
        if ($search !== null && $search !== '') {
            $where = 'AND (batch_no LIKE :search OR note LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        $sql = "SELECT * FROM payment_batches WHERE status = :status {$where} ORDER BY id DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Fetch payment batches with member & slip details for admin listing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allWithDetails(?string $status = null, ?string $search = null): array
    {
        $params = [];
        $where = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where[] = 'pb.status = :status';
            $params[':status'] = $status;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(pb.batch_no LIKE :search OR u.name LIKE :search OR u.email LIKE :search OR m.member_code LIKE :search OR pb.note LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT pb.*,
                       u.name AS member_name,
                       u.email AS member_email,
                       m.member_code,
                       m.phone AS member_phone,
                       ps.stored_name AS slip_file_name,
                       ps.original_name AS slip_original_name
                FROM payment_batches pb
                LEFT JOIN members m ON m.id = pb.member_id
                LEFT JOIN users u ON u.id = m.user_id
                LEFT JOIN payment_slips ps ON ps.id = pb.payment_slip_id
                {$whereClause}
                ORDER BY pb.id DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Quick KPI counts and sums for payment batches.
     *
     * @return array<string, mixed>
     */
    public function statsSummary(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_count,
                    COALESCE(SUM(CASE WHEN status IN ('pending_verification', 'submitted', 'pending') THEN 1 ELSE 0 END), 0) AS pending_count,
                    COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved_count,
                    COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected_count,
                    COALESCE(SUM(CASE WHEN status = 'approved' THEN total_amount ELSE 0 END), 0) AS approved_amount,
                    COALESCE(SUM(CASE WHEN status IN ('pending_verification', 'submitted', 'pending') THEN total_amount ELSE 0 END), 0) AS pending_amount
                FROM payment_batches";
        $stmt = Database::connection()->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_count' => 0,
            'pending_count' => 0,
            'approved_count' => 0,
            'rejected_count' => 0,
            'approved_amount' => 0,
            'pending_amount' => 0,
        ];
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO payment_batches (
                    batch_no, member_id, total_amount, payment_slip_id, status, note
                ) VALUES (
                    :batch_no, :member_id, :total_amount, :payment_slip_id, :status, :note
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':batch_no'        => $data['batch_no'],
            ':member_id'       => $data['member_id'],
            ':total_amount'    => $data['total_amount'],
            ':payment_slip_id' => $data['payment_slip_id'] ?? null,
            ':status'          => $data['status'] ?? 'submitted',
            ':note'            => $data['note'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = ['batch_no', 'member_id', 'total_amount', 'payment_slip_id', 'status', 'note'];
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
        $sql = 'UPDATE payment_batches SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status, ?int $verifiedBy = null): void
    {
        $sql = 'UPDATE payment_batches
                SET status = :status,
                    verified_by = :verified_by,
                    verified_at = IF(:verified_by IS NOT NULL, NOW(), verified_at),
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':status'      => $status,
            ':verified_by' => $verifiedBy,
            ':id'          => $id,
        ]);
    }

    private function hydrate(array $row): PaymentBatch
    {
        return PaymentBatch::fromRow($row);
    }
}
