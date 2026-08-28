<?php
/**
 * Financial flow E2E test:
 *  1. Member (ahmad) submits a bulk payment for outstanding schedules (no slip).
 *  2. Verify batch created + payments created + schedules updated.
 *  3. Admin approves the batch -> ledger entries + score event.
 *  4. Verify ledger + credit score in DB.
 */
$baseUrl = 'http://app';

function ch(array $c): string { $p=[]; foreach($c as $k=>$v) $p[]="$k=$v"; return implode('; ',$p); }
function parseCookies(array $h): array { $o=[]; foreach($h as $x){ if(stripos($x,'Set-Cookie:')===0){ $kv=explode('=',trim(substr($x,11)),2); if(count($kv)===2)$o[$kv[0]]=rtrim($kv[1],';'); } } return $o; }
function req(string $m,string $u,?string $b,array $c):array{ $h0=curl_init();curl_setopt($h0,CURLOPT_URL,$u);curl_setopt($h0,CURLOPT_RETURNTRANSFER,true);curl_setopt($h0,CURLOPT_HEADER,true);curl_setopt($h0,CURLOPT_FOLLOWLOCATION,false);curl_setopt($h0,CURLOPT_TIMEOUT,20); if($c)curl_setopt($h0,CURLOPT_COOKIE,ch($c)); if($m==='POST'){curl_setopt($h0,CURLOPT_POST,true);if($b!==null)curl_setopt($h0,CURLOPT_POSTFIELDS,$b);} $r=curl_exec($h0); if($r===false){echo "CURL ERR\n";return['status'=>0,'headers'=>[],'body'=>''];} $code=curl_getinfo($h0,CURLINFO_HTTP_CODE);$hs=curl_getinfo($h0,CURLINFO_HEADER_SIZE);curl_close($h0); return ['status'=>$code,'headers'=>preg_split("/\r?\n/",trim(substr($r,0,$hs))),'body'=>substr($r,$hs)]; }
function loc(array $h):?string{ foreach($h as $x) if(stripos($x,'Location:')===0) return trim(substr($x,9)); return null; }

$pass=0;$fail=0;
function check(string $label, $cond, string $d=''){ global $pass,$fail; echo ($cond?'OK  ':'FAIL')." $label".($d?" | $d\n":"\n"); $cond?$pass++:$fail++; }

// Login as ahmad
$cookies=[];
$r = req('GET', $baseUrl.'/login', null, []);
$cookies = array_merge($cookies, parseCookies($r['headers']));
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r['body'], $m);
$csrf = $m[1] ?? '';
$r = req('POST', $baseUrl.'/login', http_build_query(['csrf_token'=>$csrf,'email'=>'ahmad@mainkutu.local','password'=>'Ahmad@12345']), $cookies);
$cookies = array_merge($cookies, parseCookies($r['headers']));
check('Member login', loc($r['headers'])==='/dashboard');

// Get outstanding schedules from /payments page
$r = req('GET', $baseUrl.'/payments', null, $cookies);
check('GET /payments', $r['status']===200);

// Find schedule IDs directly from DB
define('APP_ROOT', '/var/www/html');
require '/var/www/html/app/helpers/functions.php';
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) { require $file; return; }
    $lower = APP_ROOT . '/app/' . strtolower(preg_replace('/\\\\[^\\\\]+$/', '', $relative)) . '/' . substr(strrchr($relative, '\\'), 1) . '.php';
    if (file_exists($lower)) require $lower;
});
\App\Config\Config::load();
$pdo = \App\Core\Database::connection();

$stmt = $pdo->query("SELECT id, plan_id, amount, amount_paid FROM contribution_schedules WHERE member_id = 1 AND status IN ('pending','partial') ORDER BY id LIMIT 2");
$scheds = $stmt->fetchAll();
check('Found outstanding schedules', count($scheds) >= 1, 'count='.count($scheds));

if (count($scheds) === 0) { echo "No outstanding schedules; abort.\n"; exit(1); }

// Get CSRF from bulk page
$r = req('GET', $baseUrl.'/payments/bulk', null, $cookies);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r['body'], $m);
$bcsrf = $m[1] ?? '';

// Build bulk items
$items = [];
foreach ($scheds as $s) {
    $items[] = [
        'plan_id' => (int) $s['plan_id'],
        'schedule_id' => (int) $s['id'],
        'amount' => bcsub($s['amount'], $s['amount_paid'], 2),
    ];
}
// PHP: items[0][plan_id] etc. — match the view's form field names.
$post = ['csrf_token' => $bcsrf];
foreach ($items as $i => $it) {
    $post["items[{$i}][selected]"] = '1';
    $post["items[{$i}][schedule_id]"] = $it['schedule_id'];
    $post["items[{$i}][plan_id]"] = $it['plan_id'];
    $post["items[{$i}][amount]"] = $it['amount'];
}
$r = req('POST', $baseUrl.'/payments/bulk', http_build_query($post), $cookies);
check('POST /payments/bulk', $r['status']===302 && loc($r['headers'])==='/payments', "status={$r['status']} loc=".(loc($r['headers'])??''));

// Verify DB state
$batch = $pdo->query("SELECT * FROM payment_batches ORDER BY id DESC LIMIT 1")->fetch();
check('Batch created', $batch !== false && $batch['status']==='submitted', 'status='.($batch['status']??'n/a'));
$pays = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE batch_id = :b");
$pays->execute([':b' => $batch['id']]);
check('Payments created for batch', (int)$pays->fetchColumn() === count($items));

// Logout member, login admin (default Admin@12345, must reset on first login)
// Reset admin credentials to a known state first: a previous e2e_smoke run
// may have changed the password (e.g. to NewAdmin@2026), which would make
// this test fail non-deterministically.
$adminHash = password_hash('Admin@12345', PASSWORD_BCRYPT);
$pdo->prepare('UPDATE users SET password = ?, must_reset_password = 1, failed_login_count = 0, locked_until = NULL WHERE email = ?')
    ->execute([$adminHash, 'admin@mainkutu.local']);
$cookies=[];
$r = req('GET', $baseUrl.'/logout', null, $cookies);
$r = req('GET', $baseUrl.'/login', null, []);
$cookies = parseCookies($r['headers']);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r['body'], $m);
$csrf = $m[1] ?? '';
$r = req('POST', $baseUrl.'/login', http_build_query(['csrf_token'=>$csrf,'email'=>'admin@mainkutu.local','password'=>'Admin@12345']), $cookies);
$cookies = array_merge($cookies, parseCookies($r['headers']));
$l = loc($r['headers']);
if ($l === '/reset-password') {
    // first-time forced reset — choose a password that differs from the default
    $r2 = req('GET', $baseUrl.'/reset-password', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r2['body'], $m2);
    $r2 = req('POST', $baseUrl.'/reset-password', http_build_query(['csrf_token'=>$m2[1],'password'=>'NewAdmin@2026','password_confirm'=>'NewAdmin@2026','reset_token'=>'']), $cookies);
    $cookies = array_merge($cookies, parseCookies($r2['headers']));
}
check('Admin login', loc($r['headers'])==='/dashboard' || loc($r2['headers'] ?? [''])==='/dashboard');

// Admin verification queue
$r = req('GET', $baseUrl.'/admin/payments', null, $cookies);
check('GET /admin/payments (queue)', $r['status']===200);

$r = req('GET', $baseUrl.'/admin/payments/'.$batch['id'], null, $cookies);
check('GET /admin/payments/{id} (show)', $r['status']===200);

// Approve batch
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r['body'], $m);
$acsrf = $m[1] ?? '';
$r = req('POST', $baseUrl.'/admin/payments/'.$batch['id'].'/approve', http_build_query(['csrf_token'=>$acsrf]), $cookies);
check('POST approve batch', $r['status']===302, "status={$r['status']} loc=".(loc($r['headers'])??''));

// Verify approved state in DB
$b2 = $pdo->prepare("SELECT status FROM payment_batches WHERE id=:i");
$b2->execute([':i'=>$batch['id']]);
check('Batch approved in DB', $b2->fetchColumn()==='approved');

// Ledger entries
$led = $pdo->prepare("SELECT COUNT(*) FROM ledger_transactions WHERE transaction_type='contribution'");
$led->execute();
check('Ledger contribution entries exist', (int)$led->fetchColumn() > 0);

// Credit score history (rule code may be ON_TIME_PAYMENT or PAYMENT_ON_TIME)
$csh = $pdo->query("SELECT COUNT(*) FROM credit_score_history WHERE reason_code IN ('ON_TIME_PAYMENT','PAYMENT_ON_TIME')");
check('Score event ON_TIME_PAYMENT created', (int)$csh->fetchColumn() > 0);

// Schedules now paid
$paid = $pdo->prepare("SELECT status FROM contribution_schedules WHERE id=:i");
$paid->execute([':i'=>$items[0]['schedule_id']]);
check('Schedule marked paid', $paid->fetchColumn()==='paid');

echo "\n=== RESULT: {$pass} passed, {$fail} failed ===\n";
exit($fail > 0 ? 1 : 0);
