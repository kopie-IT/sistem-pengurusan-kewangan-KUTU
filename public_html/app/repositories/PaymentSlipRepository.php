<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\PaymentSlip;
use PDO;

/**
 * Data access for the `payment_slips` table.
 */
final class PaymentSlipRepository
{
    public function findById(int $id): ?PaymentSlip
    {
        $sql = 'SELECT * FROM payment_slips WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO payment_slips (
                    member_id, stored_name, original_name, mime_type, size_bytes, purpose, uploaded_by
                ) VALUES (
                    :member_id, :stored_name, :original_name, :mime_type, :size_bytes, :purpose, :uploaded_by
                )';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':member_id'     => $data['member_id'] ?? null,
            ':stored_name'   => $data['stored_name'],
            ':original_name' => $data['original_name'] ?? null,
            ':mime_type'     => $data['mime_type'] ?? null,
            ':size_bytes'    => $data['size_bytes'] ?? 0,
            ':purpose'       => $data['purpose'] ?? 'contribution',
            ':uploaded_by'   => $data['uploaded_by'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return PaymentSlip[]
     */
    public function findByMember(int $memberId): array
    {
        $sql = 'SELECT * FROM payment_slips WHERE member_id = :member_id ORDER BY id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':member_id' => $memberId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function hydrate(array $row): PaymentSlip
    {
        return PaymentSlip::fromRow($row);
    }
}
