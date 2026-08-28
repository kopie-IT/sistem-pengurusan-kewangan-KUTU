<?php

declare(strict_types=1);

/**
 * Migration runner.
 *
 * Usage: php cron/migrate.php
 *
 * Reads .env, connects to MySQL, and applies all SQL files in
 * database/migrations/ in alphabetical order. Tracks executed
 * migrations in the `migrations` table.
 */

define('APP_ROOT', dirname(__DIR__));

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

\App\Config\Config::load();
$config = \App\Config\Config::all();

try {
    $pdo = \App\Core\Database::connection();
} catch (\Throwable $e) {
    fwrite(STDERR, "[ERROR] Cannot connect to database: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Ensure migrations table exists.
$pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `migration`  VARCHAR(255) NOT NULL,
        `executed_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_migration` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

$migrationsDir = APP_ROOT . '/database/migrations';
$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files);

$executed = [];
$rows = $pdo->query('SELECT migration FROM migrations')->fetchAll();
foreach ($rows as $row) {
    $executed[$row['migration']] = true;
}

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (isset($executed[$name])) {
        echo "[skip] $name" . PHP_EOL;
        continue;
    }

    echo "[run]  $name ... ";
    $sql = file_get_contents($file);
    if ($sql === false) {
        echo "FAILED (cannot read file)" . PHP_EOL;
        exit(1);
    }

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:m)');
        $stmt->execute([':m' => $name]);
        echo "OK" . PHP_EOL;
        $ran++;
    } catch (\Throwable $e) {
        echo "FAILED" . PHP_EOL;
        fwrite(STDERR, "  -> " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo PHP_EOL . "Migrations complete. $ran new migration(s) applied." . PHP_EOL;
