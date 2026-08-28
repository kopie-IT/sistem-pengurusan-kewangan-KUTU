<?php

declare(strict_types=1);

namespace App\Models;

final class Member
{
    public function __construct(
        public int $id,
        public int $userId,
        public ?string $memberCode,
        public ?string $phone,
        public ?string $icNumber,
        public ?string $address,
        public int $creditScore,
        public string $status,
        public string $name,
        public string $email,
        public string $createdAt,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            memberCode: $row['member_code'] ?? null,
            phone: $row['phone'] ?? null,
            icNumber: $row['ic_number'] ?? null,
            address: $row['address'] ?? null,
            creditScore: (int) ($row['credit_score'] ?? 100),
            status: $row['status'] ?? 'active',
            name: $row['name'] ?? '',
            email: $row['email'] ?? '',
            createdAt: $row['created_at'],
        );
    }
}
