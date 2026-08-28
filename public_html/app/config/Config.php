<?php

declare(strict_types=1);

namespace App\Config;

final class Config
{
    private static ?self $instance = null;

    /** @var array<string, string|null> */
    private array $values = [];

    private function __construct()
    {
        $this->loadEnv();
        $this->loadSystemEnv();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function load(): void
    {
        self::getInstance();
    }

    public static function all(): array
    {
        $instance = self::getInstance();
        return $instance->values;
    }

    private function loadEnv(): void
    {
        $envPath = APP_ROOT . '/.env';

        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
            }

            $this->values[$key] = $value;
        }
    }

    /**
     * Allow environment variables to override file values.
     */
    private function loadSystemEnv(): void
    {
        foreach (['APP_ENV', 'APP_DEBUG', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'APP_URL'] as $key) {
            $sysValue = getenv($key);
            if ($sysValue !== false) {
                $this->values[$key] = $sysValue;
            }
        }
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->values[$key] ?? $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);
        if ($value === null || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }
}
