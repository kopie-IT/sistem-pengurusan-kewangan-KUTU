<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Key/value settings used by admin to customize the application
 * (system name, logo, tagline, etc.).
 */
final class AppSettingRepository
{
    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = Database::connection()->prepare('SELECT value FROM app_settings WHERE `key` = :k LIMIT 1');
        $stmt->execute([':k' => $key]);
        $v = $stmt->fetchColumn();
        return $v === false ? $default : (string) $v;
    }

    public function all(): array
    {
        $rows = Database::connection()->query('SELECT `key`, value FROM app_settings')->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[$r['key']] = $r['value'];
        }
        return $out;
    }

    public function set(string $key, ?string $value): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO app_settings (`key`, value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([':k' => $key, ':v' => $value]);
    }
}
