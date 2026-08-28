<?php
/**
 * Comprehensive E2E smoke test:
 *  1. Login as admin (first-time reset flow) -> dashboard
 *  2. Visit all admin routes
 *  3. Login as member (ahmad, no reset needed) -> member routes
 *  4. Report pass/fail per route
 */
$baseUrl = 'http://app';
$cookies = [];

function ch(array $c): string { $p=[]; foreach($c as $k=>$v) $p[]="$k=$v"; return implode('; ',$p); }
function parseCookies(array $h): array { $o=[]; foreach($h as $x){ if(stripos($x,'Set-Cookie:')===0){ $kv=explode('=',trim(substr($x,11)),2); if(count($kv)===2)$o[$kv[0]]=rtrim($kv[1],';'); } } return $o; }
function req(string $m,string $u,?string $b,array $c):array{ $h0=curl_init();curl_setopt($h0,CURLOPT_URL,$u);curl_setopt($h0,CURLOPT_RETURNTRANSFER,true);curl_setopt($h0,CURLOPT_HEADER,true);curl_setopt($h0,CURLOPT_FOLLOWLOCATION,false);curl_setopt($h0,CURLOPT_TIMEOUT,20); if($c)curl_setopt($h0,CURLOPT_COOKIE,ch($c)); if($m==='POST'){curl_setopt($h0,CURLOPT_POST,true);if($b!==null)curl_setopt($h0,CURLOPT_POSTFIELDS,$b);} $r=curl_exec($h0); if($r===false){echo "CURL ERR ".curl_error($h0)."\n";curl_close($h0);return['status'=>0,'headers'=>[],'body'=>''];} $code=curl_getinfo($h0,CURLINFO_HTTP_CODE);$hs=curl_getinfo($h0,CURLINFO_HEADER_SIZE);curl_close($h0); return ['status'=>$code,'headers'=>preg_split("/\r?\n/",trim(substr($r,0,$hs))),'body'=>substr($r,$hs)]; }
function loc(array $h):?string{ foreach($h as $x) if(stripos($x,'Location:')===0) return trim(substr($x,9)); return null; }

$pass=0; $fail=0;
function check(string $label, $cond, string $d=''){ global $pass,$fail; echo ($cond?'OK  ':'FAIL')." $label".($d?" | $d\n":"\n"); $cond?$pass++:$fail++; }

// ---------- 1. Admin login with first-time reset ----------
$r = req('GET', $baseUrl.'/login', null, []);
$cookies = array_merge($cookies, parseCookies($r['headers']));
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r['body'], $m);
$csrf = $m[1] ?? '';
check('GET /login', $r['status']===200);

$r = req('POST', $baseUrl.'/login', http_build_query(['csrf_token'=>$csrf,'email'=>'admin@mainkutu.local','password'=>'Admin@12345']), $cookies);
$cookies = array_merge($cookies, parseCookies($r['headers']));
$l = loc($r['headers']);
check('POST /login admin', $r['status']===302 && $l==='/reset-password', "status={$r['status']} loc={$l}");

$r = req('GET', $baseUrl.'/reset-password', null, $cookies);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r['body'], $m);
$rcsrf = $m[1] ?? '';
$r = req('POST', $baseUrl.'/reset-password', http_build_query(['csrf_token'=>$rcsrf,'password'=>'NewAdmin@2026','password_confirm'=>'NewAdmin@2026','reset_token'=>'']), $cookies);
$cookies = array_merge($cookies, parseCookies($r['headers']));
$l = loc($r['headers']);
check('POST /reset-password (keep default pw)', $r['status']===302 && $l==='/dashboard', "status={$r['status']} loc={$l}");

// ---------- 2. Admin routes ----------
$adminRoutes = [
    ['GET','/dashboard','Admin dashboard'],
    ['GET','/admin','Admin control panel'],
    ['GET','/admin/plans','Admin plans list'],
    ['GET','/admin/plans/create','Admin plan form'],
    ['GET','/admin/plans/1/edit','Admin plan edit'],
    ['GET','/admin/members','Admin members'],
    ['GET','/admin/members/create','Admin member form'],
    ['GET','/admin/members/1','Admin member show'],
    ['GET','/admin/payments','Admin payment verification queue'],
    ['GET','/admin/payouts','Admin payouts'],
    ['GET','/admin/credit-scores','Admin credit scores'],
    ['GET','/admin/shortfalls','Admin shortfalls'],
    ['GET','/admin/withdrawals','Admin withdrawals'],
    ['GET','/admin/reports','Admin reports dashboard'],
    ['GET','/admin/reports/financial','Admin financial report'],
    ['GET','/admin/reports/plans','Admin plans report'],
    ['GET','/admin/reports/members','Admin members report'],
];
foreach ($adminRoutes as [$method, $path, $label]) {
    $r = req($method, $baseUrl.$path, null, $cookies);
    $expectedRedirect = $path === '/admin/reports';
    $redirectTarget = 'http://localhost:8090/admin/reports/financial';
    $ok = $expectedRedirect
        ? $r['status'] === 302 && loc($r['headers']) === $redirectTarget
        : $r['status'] === 200;
    $detail = "status={$r['status']}";
    if (!$ok && $r['status']===302) $detail .= ' loc='.loc($r['headers']);
    if (!$ok && $r['status']===500) $detail .= ' '.substr($r['body'],0,200);
    check($label." ({$path})", $ok, $detail);
}

// CSV export
$r = req('GET', $baseUrl.'/admin/reports/export', null, $cookies);
check('CSV export', $r['status']===200, 'status='.$r['status']);

// Logout
$r = req('GET', $baseUrl.'/logout', null, $cookies);
$cookies = [];
check('Logout admin', $r['status']===302 || $r['status']===200);

// ---------- 3. Member login (ahmad) ----------
$cookies = [];
$r = req('GET', $baseUrl.'/login', null, []);
$cookies = array_merge($cookies, parseCookies($r['headers']));
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r['body'], $m);
$csrf = $m[1] ?? '';
$r = req('POST', $baseUrl.'/login', http_build_query(['csrf_token'=>$csrf,'email'=>'ahmad@mainkutu.local','password'=>'Ahmad@12345']), $cookies);
$cookies = array_merge($cookies, parseCookies($r['headers']));
$l = loc($r['headers']);
check('POST /login member', $r['status']===302 && $l==='/dashboard', "status={$r['status']} loc={$l}");

$memberRoutes = [
    ['GET','/dashboard','Member dashboard'],
    ['GET','/plans','Plans list'],
    ['GET','/plans/1','Plan detail'],
    ['GET','/payments','Payments'],
    ['GET','/payments/bulk','Bulk payment'],
    ['GET','/payouts/me','My payouts'],
    ['GET','/credit-score','My credit score'],
    ['GET','/calendar/contribution','Contribution calendar'],
    ['GET','/calendar/payout','Payout calendar'],
    ['GET','/profile','Profile'],
    ['GET','/notifications','Notifications'],
    ['GET','/withdrawals','My withdrawals'],
    ['GET','/withdrawals/request','Withdrawal request form'],
];
foreach ($memberRoutes as [$method, $path, $label]) {
    $r = req($method, $baseUrl.$path, null, $cookies);
    $ok = $r['status']===200;
    $detail = "status={$r['status']}";
    if (!$ok && $r['status']===302) $detail .= ' loc='.loc($r['headers']);
    if (!$ok && $r['status']===500) $detail .= ' '.substr($r['body'],0,200);
    check($label." ({$path})", $ok, $detail);
}

// Member cannot access admin routes (should redirect)
$r = req('GET', $baseUrl.'/admin/plans', null, $cookies);
check('Member blocked from /admin/plans', $r['status']===302 && loc($r['headers'])==='/dashboard', "status={$r['status']} loc=".(loc($r['headers'])??''));

echo "\n=== RESULT: {$pass} passed, {$fail} failed ===\n";
exit($fail > 0 ? 1 : 0);
