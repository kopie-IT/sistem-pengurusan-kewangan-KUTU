#!/bin/sh
cd /var/www/html
php -r '
require "app/helpers/functions.php";
\App\Config\Config::load();
$pdo = \App\Core\Database::connection();
$rows = $pdo->query("SELECT u.id, u.email, u.name, u.status, r.name AS role FROM users u LEFT JOIN roles r ON r.id = u.role_id ORDER BY u.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
  echo $r["id"] . " | " . $r["email"] . " | " . $r["name"] . " | " . $r["status"] . " | " . $r["role"] . PHP_EOL;
}
'
