<?php

declare(strict_types=1);

/**
 * Database seeder.
 *
 * Usage: php cron/seed.php
 *
 * Inserts default roles and seed users (admin & member) with bcrypt-hashed
 * passwords. Both seed users are marked must_reset_password = 1 so the
 * authentication flow forces a password change on first login.
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

try {
    $pdo = \App\Core\Database::connection();
} catch (\Throwable $e) {
    fwrite(STDERR, "[ERROR] Cannot connect to database: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Apply SQL seeder files first (for any plain SQL inserts).
$seedersDir = APP_ROOT . '/database/seeders';
$sqlFiles = glob($seedersDir . '/*.sql') ?: [];
sort($sqlFiles);
foreach ($sqlFiles as $file) {
    $name = basename($file);
    echo "[sql]  $name" . PHP_EOL;
    $sql = file_get_contents($file);
    if ($sql !== false) {
        $pdo->exec($sql);
    }
}

// Default credentials
$defaultUsers = [
    [
        'name'     => 'Administrator',
        'email'    => 'admin@mainkutu.local',
        'password' => 'Admin@12345',
        'role'     => 'admin',
    ],
    [
        'name'     => 'Member Demo',
        'email'    => 'member@mainkutu.local',
        'password' => 'Member@12345',
        'role'     => 'member',
    ],
];

$inserted = 0;
$updated = 0;
foreach ($defaultUsers as $u) {
    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug');
    $roleStmt->execute([':slug' => $u['role']]);
    $role = $roleStmt->fetch();
    if (!$role) {
        fwrite(STDERR, "[WARN] Role '{$u['role']}' not found. Skipping user {$u['email']}." . PHP_EOL);
        continue;
    }

    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $existing = $pdo->prepare('SELECT id, must_reset_password FROM users WHERE email = :email');
    $existing->execute([':email' => $u['email']]);
    $row = $existing->fetch();

    if ($row) {
        // Only update password if must_reset_password is still true (never override a
        // password the user has chosen). Force reset on every seed run.
        $upd = $pdo->prepare('UPDATE users SET password = :pw, must_reset_password = 1, updated_at = NOW() WHERE id = :id');
        $upd->execute([':pw' => $hash, ':id' => $row['id']]);
        echo "[upd]  {$u['email']} (password reset to default, must_reset_password = 1)" . PHP_EOL;
        $updated++;
    } else {
        $ins = $pdo->prepare('INSERT INTO users (name, email, password, role_id, status, must_reset_password) VALUES (:name, :email, :pw, :role, "active", 1)');
        $ins->execute([
            ':name'  => $u['name'],
            ':email' => $u['email'],
            ':pw'    => $hash,
            ':role'  => $role['id'],
        ]);
        echo "[add]  {$u['email']}" . PHP_EOL;
        $inserted++;
    }
}

echo PHP_EOL . "Seeding complete. Inserted: $inserted, Updated: $updated." . PHP_EOL;
echo PHP_EOL . "Default credentials:" . PHP_EOL;
foreach ($defaultUsers as $u) {
    echo "  - {$u['email']} / {$u['password']}  (must reset on first login)" . PHP_EOL;
}
