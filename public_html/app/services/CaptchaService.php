<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Lightweight server-side challenge-response CAPTCHA.
 *
 * The current implementation generates a small arithmetic puzzle
 * ("Berapakah 3 + 5?") and stores the expected answer + timestamp in the
 * session. The challenge is rendered next to the form so the user types
 * the numeric answer.
 *
 * Why a math CAPTCHA instead of AWS WAF / reCAPTCHA?
 *  - Zero third-party dependency (the project is vanilla PHP, no composer).
 *  - The question + answer is held server-side in the session — bots that
 *    only POST raw fields without solving the puzzle are rejected.
 *  - Sufficient for blocking low-effort scripted logins, registration, and
 *    forgot-password abuse on a single-tenant internal app.
 *
 * The configuration (`captcha_enabled`, `captcha_secret_questions`,
 * `aws_waf_*`) lives in `system_settings` so the admin can turn the
 * feature on/off per environment. The AWS WAF keys are reserved for
 * future integration with the AWS WAF Captcha JavaScript challenge —
 * wiring the full AWS WAF API client is a future task (out of scope here
 * since the user only requested configuration storage today).
 */
final class CaptchaService
{
    public const SESSION_KEY    = 'captcha';
    public const SESSION_TS     = 'captcha_ts';
    public const SESSION_TOKEN  = 'captcha_token';
    public const TTL_SECONDS    = 600; // 10 minutes

    public function __construct(private SystemSettingService $settings) {}

    public function isEnabled(): bool
    {
        try {
            return (bool) $this->settings->get('captcha_enabled', false);
        } catch (\Throwable $e) {
            // Settings not available (DB down / config error) — fail closed
            // and don't render the captcha field.
            return false;
        }
    }

    /**
     * Whether the CAPTCHA should be required on a given page.
     * Per-page toggles let the admin turn it off for low-risk screens
     * (e.g. marketing / public landing) while keeping it on for
     * authentication, registration, and the email blast composer.
     */
    public function isRequiredOn(string $formKey): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        $key = 'captcha_on_' . $formKey;
        try {
            // Default: enabled for the sensitive forms.
            if ($this->settings->get($key, null) === null) {
                return in_array($formKey, ['login', 'register', 'forgot_password', 'reset_password', 'admin_blast'], true);
            }
            return (bool) $this->settings->get($key, false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Issue a fresh challenge and store the expected answer in the
     * session. Returns the question text (e.g. "Berapakah 3 + 5?").
     */
    public function issueChallenge(): string
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);
        // Use addition by default; very small chance of multiplication
        // (≤ 81) keeps the puzzle obvious to humans.
        $op = (random_int(0, 3) === 0) ? '*' : '+';
        $answer = $op === '*' ? $a * $b : $a + $b;

        $_SESSION[self::SESSION_KEY]   = (string) $answer;
        $_SESSION[self::SESSION_TS]    = time();
        $_SESSION[self::SESSION_TOKEN] = bin2hex(random_bytes(16));

        return $op === '*'
            ? sprintf('Berapakah %d x %d?', $a, $b)
            : sprintf('Berapakah %d + %d?', $a, $b);
    }

    public function currentQuestion(): string
    {
        if (!$this->hasFreshChallenge()) {
            return $this->issueChallenge();
        }
        // We don't store the question text (only the answer) to save
        // memory. The question is derived from the most recently issued
        // numbers — but the simplest UX is to just re-issue when the
        // form re-renders. That way the user always sees a current
        // puzzle and stale answers can never be replayed.
        return $this->issueChallenge();
    }

    public function hasFreshChallenge(): bool
    {
        if (empty($_SESSION[self::SESSION_KEY]) || empty($_SESSION[self::SESSION_TS])) {
            return false;
        }
        return (time() - (int) $_SESSION[self::SESSION_TS]) < self::TTL_SECONDS;
    }

    /**
     * Verify the user's answer. Returns true on success (and rotates the
     * challenge so it can't be reused). Returns false otherwise.
     */
    public function verify(?string $answer, ?string $token): bool
    {
        if ($answer === null || $token === null) {
            return false;
        }
        $expected = $_SESSION[self::SESSION_KEY] ?? null;
        $issued   = $_SESSION[self::SESSION_TS]  ?? null;
        $storedTk = $_SESSION[self::SESSION_TOKEN] ?? null;

        // Always rotate after a verification attempt to prevent replay.
        $this->clear();

        if ($expected === null || $issued === null || $storedTk === null) {
            return false;
        }
        if (!hash_equals((string) $storedTk, (string) $token)) {
            return false;
        }
        if ((time() - (int) $issued) > self::TTL_SECONDS) {
            return false;
        }
        if (!preg_match('/^-?\d+$/', trim($answer))) {
            return false;
        }
        return hash_equals((string) $expected, trim($answer));
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::SESSION_TS], $_SESSION[self::SESSION_TOKEN]);
    }
}
