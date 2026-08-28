<?php
/**
 * Precise login debug: capture the flash error message shown on failed login.
 */
$baseUrl = 'http://app';
$cookies = [];

function cookieHeader(array $c): string {
    $parts = [];
    foreach ($c as $k => $v) $parts[] = "$k=$v";
    return implode('; ', $parts);
}
function parseCookies(array $headers): array {
    $out = [];
    foreach ($headers as $h) {
        if (stripos($h, 'Set-Cookie:') === 0) {
            $kv = explode('=', trim(substr($h, 11)), 2);
            if (count($kv) === 2) $out[$kv[0]] = rtrim($kv[1], ';');
        }
    }
    return $out;
}
function req(string $method, string $url, ?string $body, array $cookies): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, cookieHeader($cookies));
    if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }
    $resp = curl_exec($ch);
    if ($resp === false) { echo "CURL ERROR: " . curl_error($ch) . "\n"; curl_close($ch); return ['status'=>0,'headers'=>[],'body'=>'']; }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $headers = preg_split("/\r?\n/", trim(substr($resp, 0, $hs)));
    return ['status'=>$code,'headers'=>$headers,'body'=>substr($resp, $hs)];
}
function flashFromBody(string $body): ?string {
    if (preg_match('/class="[^"]*\balert-(error|danger)\b[^"]*"[^>]*>.*?<[^>]*class="[^"]*alert-message[^"]*"[^>]*>(.*?)<\/div>/si', $body, $m)) {
        return trim(strip_tags($m[2]));
    }
    if (preg_match('/<div[^>]*class="[^"]*alert[^"]*"[^>]*>.*?<[^>]*class="[^"]*alert-message[^"]*"[^>]*>(.*?)<\/div>/si', $body, $m)) {
        return trim(strip_tags($m[1]));
    }
    if (preg_match('/<div[^>]*class="[^"]*(?:alert|flash)[^"]*"[^>]*>(.*?)<\/div>/si', $body, $m)) {
        return trim(strip_tags($m[1]));
    }
    return null;
}

// Step 1: GET login, get CSRF
$r1 = req('GET', $baseUrl . '/login', null, $cookies);
$cookies = array_merge($cookies, parseCookies($r1['headers']));
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r1['body'], $m);
$csrf = $m[1] ?? null;
echo "CSRF: " . substr($csrf ?? '', 0, 16) . "\n";
echo "Session cookie sent back to server: " . (isset($cookies['mainkutu_session']) ? 'YES (' . substr($cookies['mainkutu_session'], 0, 12) . '...)' : 'NO') . "\n\n";

// Step 2: POST login (debug: capture flash)
$body = http_build_query([
    'csrf_token' => $csrf,
    'email'      => 'admin@mainkutu.local',
    'password'   => 'Admin@12345',
]);
$r2 = req('POST', $baseUrl . '/login', $body, $cookies);
echo "=== POST /login ===\n";
echo "Status: " . $r2['status'] . "\n";
$location = null;
foreach ($r2['headers'] as $h) if (stripos($h, 'Location:') === 0) $location = trim(substr($h, 9));
echo "Location: " . ($location ?: '(none — stay on page)') . "\n";

// Fetch the page we are redirected to, and read the flash message
if ($location) {
    $next = preg_match('#^https?://#', $location) ? $location : $baseUrl . $location;
    $r3 = req('GET', $next, null, array_merge($cookies, parseCookies($r2['headers'])));
    echo "\n=== GET $next (flash capture) ===\n";
    echo "Status: " . $r3['status'] . "\n";
    $flash = flashFromBody($r3['body']);
    echo "Flash message: " . ($flash ?: '(none detected)') . "\n";
    // Also dump a snippet of the alert area
    if (preg_match('/<div[^>]*class="[^"]*alert[^"]*"[^>]*>.*?<\/div>\s*<\/div>/si', $r3['body'], $m)) {
        echo "Alert HTML: " . substr(trim($m[0]), 0, 300) . "\n";
    }
} else {
    $flash = flashFromBody($r2['body']);
    echo "Flash message: " . ($flash ?: '(none detected)') . "\n";
}

echo "\nDone.\n";
