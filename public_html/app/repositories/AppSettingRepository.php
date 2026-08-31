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
    /** @var array<string, string|null>|null Per-request cache of loaded keys. */
    private static ?array $cache = null;

    public function get(string $key, ?string $default = null): ?string
    {
        if (self::$cache === null) {
            self::$cache = $this->loadAll();
        }
        return self::$cache[$key] ?? $default;
    }

    public function all(): array
    {
        if (self::$cache === null) {
            self::$cache = $this->loadAll();
        }
        return self::$cache;
    }

    public function set(string $key, ?string $value): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO app_settings (`key`, value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([':k' => $key, ':v' => $value]);
        // Keep cache coherent so the same request sees the new value.
        self::$cache = null;
    }

    /** @return array<string, string|null> */
    private function loadAll(): array
    {
        $rows = Database::connection()->query('SELECT `key`, value FROM app_settings')->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[$r['key']] = $r['value'];
        }
        return $out;
    }
}
