<?php

declare(strict_types=1);

namespace App\Models;

final class WithdrawalRequest
{
    public static function fromRow(array $row): array
    {
        return $row;
    }
}
