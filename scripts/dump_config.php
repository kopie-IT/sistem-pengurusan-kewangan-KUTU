<?php
define('APP_ROOT', '/var/www/html');
require '/var/www/html/app/helpers/functions.php';
spl_autoload_register(function($c) {
    $p = 'App\\';
    if (!str_starts_with($c, $p)) return;
    $r = substr($c, strlen($p));
    $f = '/var/www/html/app/' . str_replace('\\', '/', $r) . '.php';
    if (file_exists($f)) require $f;
});
\App\Config\Config::load();
echo "All config keys:\n";
foreach (\App\Config\Config::all() as $k => $v) {
    echo "  $k = " . (strlen($v ?? '') > 60 ? substr($v, 0, 60) . '...' : $v) . "\n";
}
