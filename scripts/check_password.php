<?php
// Define APP_ROOT first since Config.php uses it
if (!defined('APP_ROOT')) {
    define('APP_ROOT', '/var/www/html');
}
chdir(APP_ROOT);

require APP_ROOT . '/app/helpers/functions.php';
spl_autoload_register(function($c) {
    $p = 'App\\';
    if (!str_starts_with($c, $p)) return;
    $r = substr($c, strlen($p));
    $f = APP_ROOT . '/app/' . str_replace('\\', '/', $r) . '.php';
    if (file_exists($f)) require $f;
});

\App\Config\Config::load();
$pdo = \App\Core\Database::connection();
$stmt = $pdo->prepare('SELECT id, password, must_reset_password, failed_login_count, locked_until FROM users WHERE email=:e');
$stmt->execute([':e' => 'admin@mainkutu.local']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "Admin user not found!\n";
    exit(1);
}

$hash = $row['password'];
echo "=== Admin user diagnostics ===\n";
echo "ID: " . $row['id'] . "\n";
echo "Hash: " . substr($hash, 0, 30) . "...\n";
echo "Must reset password: " . ($row['must_reset_password'] ? 'YES' : 'NO') . "\n";
echo "Failed login count: " . $row['failed_login_count'] . "\n";
echo "Locked until: " . ($row['locked_until'] ?? 'NULL') . "\n\n";

echo "Password verification:\n";
foreach (['Admin@12345', 'NewSecure#2026', 'admin123', 'password'] as $candidate) {
    echo "  - '$candidate': " . (password_verify($candidate, $hash) ? 'MATCH' : 'no match') . "\n";
}
