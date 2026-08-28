<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AppSettingRepository;
use App\Repositories\MemberRepository;
use App\Repositories\PaymentSlipRepository;
use App\Services\AuthService;

/**
 * Authenticated download of uploaded slips (payment / payout).
 *
 * Access rules:
 *  - Contribution slips: viewable by the owning member OR any admin.
 *  - Payout slips: admin-only (per spec — there is no recipient linkage on
 *    the slip row, so members are denied to avoid leaking other members'
 *    documents).
 *
 * A 403 is returned when access is denied, 404 when the slip or file is
 * missing. Files are streamed with the stored mime type and an attachment
 * disposition so they cannot be embedded/inlined.
 */
final class FileController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private PaymentSlipRepository $slips,
        private MemberRepository $members,
        private AppSettingRepository $settings,
    ) {}

    public function download(int $slipId): void
    {
        if (!$this->auth->isAuthenticated()) {
            $this->redirect('/login');
        }

        $slip = $this->slips->findById($slipId);
        if ($slip === null) {
            $this->notFound();
        }

        $isAdmin = $this->isAdmin();

        if ($slip->purpose === 'payout') {
            // Payout slips: admin-only.
            if (!$isAdmin) {
                $this->forbidden();
            }
        } else {
            // Contribution slips: owner member OR admin.
            $currentMemberId = $this->memberId();
            if (!$isAdmin && $slip->memberId !== $currentMemberId) {
                $this->forbidden();
            }
        }

        $dir = APP_ROOT . '/storage/uploads/' . ($slip->purpose === 'payout' ? 'payout-slips' : 'payment-slips') . '/';
        $path = $dir . $slip->storedName;

        if (!is_file($path)) {
            $this->notFound();
        }

        $mime = $slip->mimeType ?? 'application/octet-stream';
        $filename = $slip->originalName ?? ('slip-' . $slipId);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . rawurlencode(basename($filename)) . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        readfile($path);
        exit;
    }

    private function forbidden(): void
    {
        http_response_code(403);
        echo 'Akses ditolak.';
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo 'Fail tidak dijumpai.';
        exit;
    }

    /**
     * Public route: stream the configured brand logo.
     * No authentication is required so login / public pages can render it.
     */
    public function brandLogo(): void
    {
        $this->streamBrandAsset((string) ($this->settings->get('logo_path') ?? ''));
    }

    /**
     * Public route: stream the system-wide payment QR.
     * No authentication is required so login / public pages can render it.
     */
    public function brandQr(): void
    {
        $this->streamBrandAsset((string) ($this->settings->get('payment_qr_path') ?? ''));
    }

    /**
     * Public route: stream a plan-specific payment QR.
     * Falls back to the system-wide QR if the plan has none configured.
     */
    public function planQr(int $planId): void
    {
        $plan = (new \App\Repositories\PlanRepository())->findById($planId);
        if ($plan === null) {
            $this->notFound();
        }
        $stored = (string) ($plan->paymentQrPath ?? '');
        if ($stored === '') {
            $stored = (string) ($this->settings->get('payment_qr_path') ?? '');
        }
        $this->streamBrandAsset($stored);
    }

    /**
     * Authenticated route: stream a user's avatar. The viewer must either
     * be the avatar's owner or an admin (admin / super_admin / staff).
     * Used by the header dropdown and the profile page.
     */
    public function userAvatar(int $userId): void
    {
        if (!$this->auth->isAuthenticated()) {
            $this->redirect('/login');
        }

        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        $isAdmin = $this->isAdmin();
        if (!$isAdmin && $currentUserId !== $userId) {
            $this->forbidden();
        }

        $stored = (new \App\Repositories\UserRepository())->getAvatarPath($userId);
        if ($stored === null || $stored === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $stored)) {
            $this->notFound();
        }
        if (!str_starts_with($stored, 'avatar_')) {
            $this->notFound();
        }

        $path = APP_ROOT . '/storage/uploads/avatars/' . $stored;
        if (!is_file($path)) {
            $this->notFound();
        }

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png'  => 'image/png',
            'jpg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            default => 'application/octet-stream',
        };

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=300');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    private function streamBrandAsset(string $stored): void
    {
        if ($stored === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $stored)) {
            $this->notFound();
        }
        $path = APP_ROOT . '/storage/uploads/brand/' . $stored;
        if (!is_file($path)) {
            $this->notFound();
        }
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png'      => 'image/png',
            'jpg',
            'jpeg'     => 'image/jpeg',
            'svg'      => 'image/svg+xml',
            'webp'     => 'image/webp',
            default    => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
