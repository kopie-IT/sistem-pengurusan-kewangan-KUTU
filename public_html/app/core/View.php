<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private const VIEW_DIR = APP_ROOT . '/app/views/';

    public static function render(string $template, array $data = []): void
    {
        $file = self::VIEW_DIR . $template . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("View '{$template}' not found.");
        }

        extract($data, EXTR_SKIP);

        $layout = $data['layout'] ?? 'layouts/main';
        $content = self::renderPartial($template, $data);

        $layoutFile = self::VIEW_DIR . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function renderPartial(string $template, array $data = []): string
    {
        $file = self::VIEW_DIR . $template . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("View '{$template}' not found.");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return ob_get_clean() ?: '';
    }

    public static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        $token = self::csrfToken();
        return '<input type="hidden" name="csrf_token" value="' . self::e($token) . '">';
    }

    public static function flashMessages(): string
    {
        $types = ['success' => '✓', 'error' => '!', 'info' => 'i'];
        $html = '';

        foreach ($types as $key => $icon) {
            if (empty($_SESSION['flash'][$key])) {
                continue;
            }

            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);

            $html .= '<div class="flash flash-' . $key . '" role="alert" data-flash>'
                . '<span class="flash-icon" aria-hidden="true">' . $icon . '</span>'
                . '<div class="flash-body">' . self::e($message) . '</div>'
                . '<button type="button" class="flash-close" aria-label="Tutup">&times;</button>'
                . '</div>';
        }

        return $html;
    }
}
