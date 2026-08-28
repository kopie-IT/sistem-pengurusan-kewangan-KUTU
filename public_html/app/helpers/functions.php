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
        return '/' . ltrim($path, '/');
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
