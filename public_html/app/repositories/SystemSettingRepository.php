<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Data access for the `system_settings` table. Rows are returned as arrays.
 */
final class SystemSettingRepository
{
    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $sql = 'SELECT setting_value, setting_type FROM system_settings WHERE setting_key = :key LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return $default;
        }
        return $this->castValue($row['setting_value'], $row['setting_type']);
    }

    public function set(string $key, $value, string $type = 'string', ?string $description = null): void
    {
        $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        $sql = 'INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
                VALUES (:key, :value, :type, :description)
                ON DUPLICATE KEY UPDATE setting_value = :value_u, setting_type = :type_u, description = :description_u';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':key'          => $key,
            ':value'        => $stored,
            ':type'         => $type,
            ':description'  => $description,
            ':value_u'      => $stored,
            ':type_u'       => $type,
            ':description_u' => $description,
        ]);
    }

    /**
     * @return array[]
     */
    public function all(): array
    {
        $sql = 'SELECT * FROM system_settings ORDER BY setting_key ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function castValue($value, string $type)
    {
        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'decimal' => (string) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
