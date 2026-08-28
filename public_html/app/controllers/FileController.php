<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
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
    ) {}

    public function view(int $slipId): void
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
}
