<?php
/**
 * End-to-end login smoke test against the app container's network alias.
 */

$baseUrl = 'http://app';
$cookies = [];

function parseCookiesFromResponse(array $headers): array {
    $out = [];
    foreach ($headers as $h) {
        if (stripos($h, 'Set-Cookie:') === 0) {
            $parts = explode(';', substr($h, 11));
            $first = trim($parts[0]);
            $kv = explode('=', $first, 2);
            if (count($kv) === 2) {
                $out[trim($kv[0])] = trim($kv[1]);
            }
        }
    }
    return $out;
}
function cookieHeader(array $cookies): string {
    $parts = [];
    foreach ($cookies as $k => $v) $parts[] = "$k=$v";
    return implode('; ', $parts);
}
function request(string $method, string $url, ?string $body, array $cookies): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, cookieHeader($cookies));
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp = curl_exec($ch);
    if ($resp === false) {
        echo "CURL ERROR: " . curl_error($ch) . "\n";
        curl_close($ch);
        return ['status' => 0, 'headers' => [], 'body' => ''];
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $rawHeaders = substr($resp, 0, $headerSize);
    $body = substr($resp, $headerSize);
    curl_close($ch);
    $headers = preg_split("/\r?\n/", trim($rawHeaders));
    return ['status' => $code, 'headers' => $headers, 'body' => $body];
}
function extractFlash(array $body): void {
    foreach ($body as $b) {
        if (preg_match('/<div[^>]*class="[^"]*alert[^"]*"[^>]*>(.*?)<\/div>/si', $b, $m)) {
            echo "Flash: " . trim(strip_tags($m[1])) . "\n";
        }
    }
}
function findLocation(array $headers): ?string {
    foreach ($headers as $h) {
        if (stripos($h, 'Location:') === 0) {
            return trim(substr($h, 9));
        }
    }
    return null;
}
function dumpHeaders(array $headers, string $filter = ''): void {
    foreach ($headers as $h) {
        if ($filter === '' || preg_match('/^(' . preg_quote($filter, '/') . '):/i', $h)) {
            echo "  " . $h . "\n";
        }
    }
}

function run(string $label, string $method, string $url, ?string $body, array $cookies): array {
    echo "\n=== $label ===\n";
    $r = request($method, $url, $body, $cookies);
    echo "Status: " . $r['status'] . "\n";
    echo "Headers:\n";
    dumpHeaders($r['headers']);
    $loc = findLocation($r['headers']);
    if ($loc) echo "Location: $loc\n";
    if (preg_match('/<div[^>]*class="[^"]*alert[^"]*"[^>]*>(.*?)<\/div>/si', $r['body'], $m)) {
        echo "Flash: " . trim(strip_tags($m[1])) . "\n";
    }
    return ['response' => $r, 'location' => $loc, 'cookies' => parseCookiesFromResponse($r['headers'])];
}

// === Step 1: GET /login ===
$step1 = run('Step 1: GET /login', 'GET', $baseUrl . '/login', null, []);
$cookies = array_merge($cookies, $step1['cookies']);
$csrf = null;
if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $step1['response']['body'], $m)) {
    $csrf = $m[1];
} elseif (preg_match('/name="_token"\s+value="([^"]+)"/', $step1['response']['body'], $m)) {
    $csrf = $m[1];
}
if (!$csrf) { echo "Could not find CSRF token!\n"; exit(1); }
echo "CSRF: " . substr($csrf, 0, 20) . "...\n";

// === Step 2: POST /login with admin creds ===
$body = http_build_query([
    'csrf_token' => $csrf,
    'email'      => 'admin@mainkutu.local',
    'password'   => 'Admin@12345',
]);
$step2 = run('Step 2: POST /login', 'POST', $baseUrl . '/login', $body, $cookies);
$cookies = array_merge($cookies, $step2['cookies']);
$loc2 = $step2['location'];

// Follow redirect chain
$hops = 0;
$currentLoc = $loc2;
while ($currentLoc && $hops < 5) {
    $hops++;
    if (!preg_match('#^https?://#', $currentLoc)) $currentLoc = $baseUrl . $currentLoc;
    $step = run("Step 2.$hops: GET $currentLoc", 'GET', $currentLoc, null, $cookies);
    $cookies = array_merge($cookies, $step['cookies']);
    $currentLoc = $step['location'];
}

// If we landed on reset-password, complete the first-time reset
$resetCsrf = null;
if ($currentLoc === null && preg_match('/name="csrf_token"\s+value="([^"]+)"/', $step['response']['body'], $m)) {
    $resetCsrf = $m[1];
    echo "\n--- Landed on reset-password page. Completing first-time reset ---\n";
    $newPw = 'NewPass#2026';
    $body = http_build_query([
        'csrf_token'       => $resetCsrf,
        'password'         => $newPw,
        'password_confirm' => $newPw,
        'reset_token'      => '',
    ]);
    $stepReset = run('Step 3: POST /reset-password', 'POST', $baseUrl . '/reset-password', $body, $cookies);
    $cookies = array_merge($cookies, $stepReset['cookies']);
    $currentLoc = $stepReset['location'];
    // follow redirect
    if ($currentLoc) {
        if (!preg_match('#^https?://#', $currentLoc)) $currentLoc = $baseUrl . $currentLoc;
        $stepAfter = run("Step 4: GET $currentLoc", 'GET', $currentLoc, null, $cookies);
        echo "Final body preview: " . substr($stepAfter['response']['body'], 0, 600) . "\n";
    }
} else {
    echo "\n--- Final body preview ---\n";
    echo substr($step['response']['body'], 0, 600) . "\n";
}

echo "\n=== DONE ===\n";
