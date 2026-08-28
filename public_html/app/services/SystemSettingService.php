<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SystemSettingRepository;

/**
 * Typed accessor for global system configuration. Values are cached for the
 * duration of a single request to avoid repeated DB lookups.
 */
final class SystemSettingService
{
    private bool $loaded = false;
    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(
        private SystemSettingRepository $repo,
    ) {}

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (!$this->loaded) {
            $this->load();
        }
        return $this->cache[$key] ?? $default;
    }

    public function set(string $key, $value, string $type = 'string', ?string $description = null): void
    {
        $this->repo->set($key, $value, $type, $description);
        // Refresh cache on the next read.
        $this->loaded = false;
    }

    /**
     * @param array<string, mixed> $kv
     */
    public function setMany(array $kv, string $type = 'string'): void
    {
        foreach ($kv as $k => $v) {
            $this->set($k, $v, $type);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (!$this->loaded) {
            $this->load();
        }
        return $this->cache;
    }

    private function load(): void
    {
        $this->cache = [];
        foreach ($this->repo->all() as $row) {
            $value = $row['setting_value'];
            $type  = $row['setting_type'];
            $this->cache[$row['setting_key']] = match ($type) {
                'int', 'integer'      => (int) $value,
                'float', 'decimal'    => (string) $value,
                'bool', 'boolean'     => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                default               => $value,
            };
        }
        $this->loaded = true;
    }
}
