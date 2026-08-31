<?php

declare(strict_types=1);

// Path to the application root (parent of public/)
defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/app/helpers/functions.php';

// Enable PHP-level gzip if Apache mod_deflate is unavailable. Cheap fallback
// that compresses all text responses (HTML/CSS/JS/JSON) without extra config.
if (function_exists('ob_gzhandler') && (int) ini_get('zlib.output_compression') === 0 && empty($_SERVER['HTTP_X_NO_COMPRESSION'])) {
    if (ob_start('ob_gzhandler') === false) {
        ob_start();
    }
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
        return;
    }

    // Fallback: case-insensitive directory (e.g. app/models vs app/Models).
    // Skip regex on hot path: derive dir + leaf directly.
    $pos = strrpos($relative, '\\');
    if ($pos !== false) {
        $dir  = strtolower(str_replace('\\', '/', substr($relative, 0, $pos)));
        $leaf = substr($relative, $pos + 1);
        $lower = APP_ROOT . '/app/' . $dir . '/' . $leaf . '.php';
        if (is_file($lower)) {
            require $lower;
        }
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
