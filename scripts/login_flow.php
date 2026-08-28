<?php
/**
 * Clean login flow test:
 *   1. GET /login -> obtain CSRF
 *   2. POST /login (admin / Admin@12345)
 *   3. Expect 302 -> /reset-password (first-time reset, by design)
 *   4. GET /reset-password -> should be 200 (authenticated, force flag set)
 *   5. POST /reset-password with new password -> 302 -> /dashboard
 *   6. GET /dashboard -> 200
 */
$baseUrl = 'http://app';
$cookies = [];

function cookieHeader(array $c): string { $p=[]; foreach($c as $k=>$v) $p[]="$k=$v"; return implode('; ',$p); }
function parseCookies(array $headers): array { $o=[]; foreach($headers as $h){ if(stripos($h,'Set-Cookie:')===0){ $kv=explode('=',trim(substr($h,11)),2); if(count($kv)===2)$o[$kv[0]]=rtrim($kv[1],';'); } } return $o; }
function req(string $m,string $u,?string $b,array $c):array{ $ch=curl_init();curl_setopt($ch,CURLOPT_URL,$u);curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);curl_setopt($ch,CURLOPT_HEADER,true);curl_setopt($ch,CURLOPT_FOLLOWLOCATION,false);curl_setopt($ch,CURLOPT_TIMEOUT,15); if($c)curl_setopt($ch,CURLOPT_COOKIE,cookieHeader($c)); if($m==='POST'){curl_setopt($ch,CURLOPT_POST,true);if($b!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$b);} $r=curl_exec($ch); if($r===false){echo "CURL ERR: ".curl_error($ch)."\n";curl_close($ch);return['status'=>0,'headers'=>[],'body'=>''];} $code=curl_getinfo($ch,CURLINFO_HTTP_CODE);$hs=curl_getinfo($ch,CURLINFO_HEADER_SIZE);curl_close($ch); return ['status'=>$code,'headers'=>preg_split("/\r?\n/",trim(substr($r,0,$hs))),'body'=>substr($r,$hs)]; }
function loc(array $h):?string{ foreach($h as $x) if(stripos($x,'Location:')===0) return trim(substr($x,9)); return null; }
function flash(string $body):?string{ if(preg_match('/<div[^>]*class="[^"]*(?:alert|flash)[^"]*"[^>]*>(.*?)<\/div>/si',$body,$m)) return trim(strip_tags($m[1])); return null; }

$ok = true;
function check(string $label, $cond, string $detail='') { global $ok; echo ($cond?'✓':'✗') . " $label" . ($detail?" — $detail\n":"\n"); if(!$cond)$ok=false; }

// 1
$r1 = req('GET',$baseUrl.'/login',null,$cookies);
$cookies = array_merge($cookies, parseCookies($r1['headers']));
preg_match('/name="csrf_token"\s+value="([^"]+)"/',$r1['body'],$m);
$csrf = $m[1] ?? null;
check('GET /login = 200', $r1['status']===200, "status {$r1['status']}");
check('CSRF token present', !empty($csrf));

// 2
$body = http_build_query(['csrf_token'=>$csrf,'email'=>'admin@mainkutu.local','password'=>'Admin@12345']);
$r2 = req('POST',$baseUrl.'/login',$body,$cookies);
$cookies = array_merge($cookies, parseCookies($r2['headers']));
$l2 = loc($r2['headers']);
check('POST /login = 302', $r2['status']===302, "status {$r2['status']}");
check('Redirects to /reset-password (first-time reset flow)', $l2==='/reset-password', "got: " . ($l2??'(none)'));

// 3
$r3 = req('GET',$baseUrl.'/reset-password',null,$cookies);
check('GET /reset-password = 200 (authenticated)', $r3['status']===200, "status {$r3['status']}");

// 4 - extract reset csrf
preg_match('/name="csrf_token"\s+value="([^"]+)"/',$r3['body'],$m);
$rcsrf = $m[1] ?? null;
$newPw = 'NewPass#2026';
$body2 = http_build_query(['csrf_token'=>$rcsrf,'password'=>$newPw,'password_confirm'=>$newPw,'reset_token'=>'']);
$r4 = req('POST',$baseUrl.'/reset-password',$body2,$cookies);
$cookies = array_merge($cookies, parseCookies($r4['headers']));
$l4 = loc($r4['headers']);
check('POST /reset-password = 302', $r4['status']===302, "status {$r4['status']}");
check('Redirects to /dashboard', $l4==='/dashboard', "got: " . ($l4??'(none)'));

// 5
$r5 = req('GET', $baseUrl.'/dashboard', null, $cookies);
check('GET /dashboard = 200', $r5['status']===200, "status {$r5['status']}");
if ($r5['status']===200) {
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $r5['body'], $m)) {
        echo "  Dashboard heading: " . trim(strip_tags($m[1])) . "\n";
    }
}

echo "\n" . ($ok ? "ALL CHECKS PASSED ✅" : "SOME CHECKS FAILED ❌") . "\n";
