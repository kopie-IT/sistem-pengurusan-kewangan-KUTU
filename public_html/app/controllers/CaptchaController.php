<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CaptchaService;

final class CaptchaController extends Controller
{
    public function __construct(private CaptchaService $captcha) {}

    /**
     * Returns a freshly-issued challenge (JSON) so the JS refresh button
     * can update the question without reloading the form. Disabled if
     * CAPTCHA is off.
     */
    public function refresh(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        if (!$this->captcha->isEnabled()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'disabled']);
            return;
        }

        $question = $this->captcha->issueChallenge();
        $token = (string) ($_SESSION[CaptchaService::SESSION_TOKEN] ?? '');

        echo json_encode([
            'ok'       => true,
            'question' => $question,
            'token'    => $token,
        ]);
    }
}
