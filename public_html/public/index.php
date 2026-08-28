<?php

declare(strict_types=1);

// Path to the application root (parent of public/)
defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/helpers/functions.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
        return;
    }

    // Fallback: case-insensitive directory (e.g. app/models vs app/Models)
    $lower = APP_ROOT . '/app/' . strtolower(preg_replace('/\\\\[^\\\\]+$/', '', $relative)) . '/' . substr(strrchr($relative, '\\'), 1) . '.php';
    if (file_exists($lower)) {
        require $lower;
    }
});

// Load config first so session settings can use .env values.
\App\Config\Config::load();

// Configure secure session before starting.
$sessionName = config('SESSION_NAME', 'mainkutu_session');
$sessionSecure = filter_var(config('SESSION_SECURE', 'true'), FILTER_VALIDATE_BOOLEAN);
$sessionHttpOnly = filter_var(config('SESSION_HTTP_ONLY', 'true'), FILTER_VALIDATE_BOOLEAN);
$sessionSameSite = config('SESSION_SAME_SITE', 'Strict');
$sessionLifetime = (int) config('SESSION_LIFETIME', '120');

session_name($sessionName);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $sessionSecure,
    'httponly' => $sessionHttpOnly,
    'samesite' => $sessionSameSite,
]);

ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
ini_set('session.use_strict_mode', '1');

session_start();

require APP_ROOT . '/app/routes/web.php';
