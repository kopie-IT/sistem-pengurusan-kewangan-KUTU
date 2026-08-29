<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, ?string $default = null): ?string
    {
        return \App\Config\Config::getInstance()->get($key, $default);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        // Cache-busting: append a version based on the file's last-modified time
        // so browsers always fetch the latest CSS/JS after an update.
        $file = dirname(__DIR__, 2) . '/public/' . ltrim($path, '/');
        $ver = is_file($file) ? (string) filemtime($file) : '0';
        return '/' . ltrim($path, '/') . '?v=' . $ver;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(config('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\View::csrfField();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\View::csrfToken();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $key): ?string
    {
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $value = $_SESSION['old'][$key] ?? $default;
        if (is_string($value)) {
            unset($_SESSION['old'][$key]);
        }
        return (string) $value;
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('flash_messages')) {
    function flash_messages(): string
    {
        return \App\Core\View::flashMessages();
    }
}

if (!function_exists('partial')) {
    function partial(string $template, array $data = []): string
    {
        return \App\Core\View::renderPartial('partials/' . $template, $data);
    }
}

if (!function_exists('format_money')) {
    function format_money(string|float|int $amount): string
    {
        $symbol = config('CURRENCY_SYMBOL', 'RM');
        return $symbol . number_format((float) $amount, 2);
    }
}

if (!function_exists('brand_name')) {
    /**
     * Resolves the configured application name with a sensible fallback.
     * Cached per-request to avoid repeated DB lookups.
     */
    function brand_name(): string
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $repo = new \App\Repositories\AppSettingRepository();
            $name = $repo->get('app_name', 'Sistem Pengurusan Main Kutu');
            $cache = $name !== null && $name !== '' ? $name : 'Sistem Pengurusan Main Kutu';
        } catch (\Throwable $e) {
            $cache = 'Sistem Pengurusan Main Kutu';
        }
        return $cache;
    }
}

if (!function_exists('brand_logo_url')) {
    /**
     * Public URL for the configured brand logo, or null if none.
     */
    function brand_logo_url(): ?string
    {
        try {
            $repo = new \App\Repositories\AppSettingRepository();
            $stored = $repo->get('logo_path');
            return ($stored !== null && $stored !== '') ? '/brand/logo' : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('brand_initials')) {
    /**
     * Two-letter initials derived from the brand name. Used as a fallback
     * when no logo has been uploaded.
     */
    function brand_initials(): string
    {
        $name = brand_name();
        $clean = preg_replace('/\s+/u', '', (string) $name) ?? '';
        $initials = strtoupper(substr($clean, 0, 2));
        return $initials !== '' ? $initials : 'MK';
    }
}

if (!function_exists('user_initials')) {
    /**
     * Two-letter initials for a user, suitable for the avatar fallback
     * bubble. Prefers the first letter of the first two whitespace-separated
     * words so "Ahmad bin Ali" → "AB".
     */
    function user_initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'U';
        }
        $parts = preg_split('/\s+/u', $name) ?: [];
        $first = strtoupper(substr($parts[0] ?? '', 0, 1));
        $second = strtoupper(substr($parts[1] ?? '', 0, 1));
        $initials = trim($first . $second);
        if ($initials === '') {
            $clean = preg_replace('/\s+/u', '', $name) ?? '';
            $initials = strtoupper(substr($clean, 0, 2));
        }
        return $initials !== '' ? $initials : 'U';
    }
}

if (!function_exists('user_avatar_url')) {
    /**
     * Public URL for the given user's avatar (auth-gated via /file/avatar/{id}).
     * Returns null when no avatar has been uploaded so the caller can fall
     * back to a coloured initials bubble.
     */
    function user_avatar_url(?int $userId): ?string
    {
        if ($userId === null || $userId <= 0) {
            return null;
        }
        try {
            $repo = new \App\Repositories\UserRepository();
            $stored = $repo->getAvatarPath($userId);
        } catch (\Throwable $e) {
            return null;
        }
        return ($stored !== null && $stored !== '') ? '/file/avatar/' . $userId : null;
    }
}

if (!function_exists('captcha_service')) {
    /**
     * Resolves a CaptchaService (caches per-request). The required
     * SystemSettingService is built on demand — but to keep this helper
     * independent of the DI container (so it can be called before the
     * container is built), we lazily construct it with a SystemSettingRepository.
     */
    function captcha_service(): \App\Services\CaptchaService
    {
        static $cache = null;
        if ($cache === null) {
            $system = new \App\Services\SystemSettingService(new \App\Repositories\SystemSettingRepository());
            $cache = new \App\Services\CaptchaService($system);
        }
        return $cache;
    }
}

if (!function_exists('captcha_field')) {
    /**
     * Renders the CAPTCHA challenge + answer input for a given form key.
     * Returns an empty string when CAPTCHA is disabled for that form.
     */
    function captcha_field(string $formKey): string
    {
        $svc = captcha_service();
        if (!$svc->isRequiredOn($formKey)) {
            return '';
        }
        $question = $svc->currentQuestion();
        $token    = (string) ($_SESSION[\App\Services\CaptchaService::SESSION_TOKEN] ?? '');

        $hQuestion = htmlspecialchars($question, ENT_QUOTES, 'UTF-8');
        $hToken    = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $hFormKey  = htmlspecialchars($formKey, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div class="form-group captcha-field" data-captcha-form="{$hFormKey}">
    <label for="captcha_answer_{$hFormKey}" class="form-label">Pengesahan Anti-Spam</label>
    <div class="captcha-challenge">
        <span class="captcha-question" aria-live="polite">{$hQuestion}</span>
        <button type="button" class="captcha-refresh" data-captcha-refresh
                aria-label="Tukar soalan captcha" title="Tukar soalan">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        </button>
    </div>
    <input type="hidden" name="captcha_token_{$hFormKey}" value="{$hToken}">
    <input type="number" id="captcha_answer_{$hFormKey}" name="captcha_answer_{$hFormKey}"
           class="form-control" required min="0" max="999"
           inputmode="numeric" autocomplete="off"
           placeholder="Taip jawapan">
    <p class="form-help">Soalan ringkas untuk pastikan anda bukan robot.</p>
</div>
HTML;
    }
}
