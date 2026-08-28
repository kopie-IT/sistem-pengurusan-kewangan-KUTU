<?php
define('APP_ROOT', __DIR__);
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
    $lower = APP_ROOT . '/app/' . strtolower(preg_replace('/\\\\[^\\\\]+$/', '', $relative)) . '/' . substr(strrchr($relative, '\\'), 1) . '.php';
    if (file_exists($lower)) {
        require $lower;
    }
});
\App\Config\Config::load();
$pdo = \App\Core\Database::connection();
$rows = $pdo->query("SELECT u.id, u.email, u.name, u.status, u.must_reset_password, r.name AS role FROM users u LEFT JOIN roles r ON r.id = u.role_id ORDER BY u.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['id'] . ' | ' . $r['email'] . ' | ' . $r['name'] . ' | ' . $r['status'] . ' | ' . $r['role'] . ' | must_reset=' . ($r['must_reset_password'] ? 'Y' : 'N') . PHP_EOL;
}
