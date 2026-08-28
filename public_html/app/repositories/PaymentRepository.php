<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Payment;
use PDO;

/**
 * Data access for the `payments` table.
 */
final class PaymentRepository
{
    public function findById(int $id): ?Payment
    {
        $sql = 'SELECT * FROM payments WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return Payment[]
     */
    public function allForMember(int $memberId): array
    {
        $sql = 'SELECT * FROM payments WHERE member_id = :member_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @return Payment[]
     */
    public function allForBatch(int $batchId): array
    {
        $sql = 'SELECT * FROM payments WHERE batch_id = :batch_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':batch_id' => $batchId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO payments (
                    member_id, plan_id, contribution_schedule_id, batch_id, amount,
                    status, payment_slip_id, note
                ) VALUES (
                    :member_id, :plan_id, :contribution_schedule_id, :batch_id, :amount,
                    :status, :payment_slip_id, :note
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':member_id'              => $data['member_id'],
            ':plan_id'                => $data['plan_id'],
            ':contribution_schedule_id' => $data['contribution_schedule_id'] ?? null,
            ':batch_id'               => $data['batch_id'] ?? null,
            ':amount'                 => $data['amount'],
            ':status'                 => $data['status'] ?? 'submitted',
            ':payment_slip_id'        => $data['payment_slip_id'] ?? null,
            ':note'                   => $data['note'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $columns = [
            'member_id', 'plan_id', 'contribution_schedule_id', 'batch_id', 'amount',
            'status', 'payment_slip_id', 'note',
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
        $sql = 'UPDATE payments SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public function updateStatus(int $id, string $status, ?int $verifiedBy = null): void
    {
        $sql = 'UPDATE payments
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

    private function hydrate(array $row): Payment
    {
        return Payment::fromRow($row);
    }
}
