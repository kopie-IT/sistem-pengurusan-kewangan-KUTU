<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, ?string $default = null): ?string
    {
        return \App\Config\Config::getInstance()->get($key, $default);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        // Cache-busting: append a version based on the file's last-modified time
        // so browsers always fetch the latest CSS/JS after an update.
        $file = dirname(__DIR__, 2) . '/public/' . ltrim($path, '/');
        $ver = is_file($file) ? (string) filemtime($file) : '0';
        return '/' . ltrim($path, '/') . '?v=' . $ver;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(config('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\View::csrfField();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\View::csrfToken();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $key): ?string
    {
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $value = $_SESSION['old'][$key] ?? $default;
        if (is_string($value)) {
            unset($_SESSION['old'][$key]);
        }
        return (string) $value;
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('flash_messages')) {
    function flash_messages(): string
    {
        return \App\Core\View::flashMessages();
    }
}

if (!function_exists('partial')) {
    function partial(string $template, array $data = []): string
    {
        return \App\Core\View::renderPartial('partials/' . $template, $data);
    }
}

if (!function_exists('format_money')) {
    function format_money(string|float|int $amount): string
    {
        $symbol = config('CURRENCY_SYMBOL', 'RM');
        return $symbol . number_format((float) $amount, 2);
    }
}

if (!function_exists('brand_name')) {
    /**
     * Resolves the configured application name with a sensible fallback.
     * Cached per-request to avoid repeated DB lookups.
     */
    function brand_name(): string
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $repo = new \App\Repositories\AppSettingRepository();
            $name = $repo->get('app_name', 'Sistem Pengurusan Main Kutu');
            $cache = $name !== null && $name !== '' ? $name : 'Sistem Pengurusan Main Kutu';
        } catch (\Throwable $e) {
            $cache = 'Sistem Pengurusan Main Kutu';
        }
        return $cache;
    }
}

if (!function_exists('brand_logo_url')) {
    /**
     * Public URL for the configured brand logo, or null if none.
     */
    function brand_logo_url(): ?string
    {
        try {
            $repo = new \App\Repositories\AppSettingRepository();
            $stored = $repo->get('logo_path');
            return ($stored !== null && $stored !== '') ? '/brand/logo' : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('brand_initials')) {
    /**
     * Two-letter initials derived from the brand name. Used as a fallback
     * when no logo has been uploaded.
     */
    function brand_initials(): string
    {
        $name = brand_name();
        $clean = preg_replace('/\s+/u', '', (string) $name) ?? '';
        $initials = strtoupper(substr($clean, 0, 2));
        return $initials !== '' ? $initials : 'MK';
    }
}
