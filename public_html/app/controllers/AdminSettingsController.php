<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AppSettingRepository;
use App\Services\AuditService;

/**
 * Admin-only settings page: app name, brand tagline, and logo upload.
 *
 * Logo upload validates MIME / size / extension, then stores the file under
 * `storage/uploads/brand/`. The stored path is persisted in `app_settings`
 * so it can be served from public without exposing the storage directory.
 */
final class AdminSettingsController extends Controller
{
    private const ALLOWED_EXT  = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
    private const ALLOWED_MIME = [
        'image/png',
        'image/jpeg',
        'image/svg+xml',
        'image/webp',
    ];
    private const MAX_BYTES    = 2 * 1024 * 1024; // 2 MB

    public function __construct(
        private AppSettingRepository $settings,
    ) {}

    public function index(): void
    {
        $this->view('admin/settings', [
            'title'    => 'Tetapan Sistem',
            'settings' => $this->settings->all(),
        ]);
    }

    public function update(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/settings');
        }

        $appName = trim((string) ($_POST['app_name'] ?? ''));
        $tagline = trim((string) ($_POST['brand_tagline'] ?? ''));
        $removeLogo = isset($_POST['remove_logo']);
        $removeQr   = isset($_POST['remove_qr']);

        if ($appName === '') {
            set_flash('error', 'Nama sistem tidak boleh kosong.');
            $this->redirect('/admin/settings');
        }
        if (mb_strlen($appName) > 150) {
            set_flash('error', 'Nama sistem melebihi 150 aksara.');
            $this->redirect('/admin/settings');
        }

        $currentLogo = $this->settings->get('logo_path');
        $currentQr   = $this->settings->get('payment_qr_path');

        // Persist text fields first.
        $this->settings->set('app_name', $appName);
        $this->settings->set('brand_tagline', $tagline !== '' ? $tagline : null);

        // Handle logo removal.
        if ($removeLogo && $currentLogo) {
            $this->deleteBrandFile($currentLogo, 'logo');
            $this->settings->set('logo_path', null);
            $currentLogo = null;
        }

        // Handle QR removal.
        if ($removeQr && $currentQr) {
            $this->deleteBrandFile($currentQr, 'qr');
            $this->settings->set('payment_qr_path', null);
            $currentQr = null;
        }

        // Handle logo upload.
        $newLogoError = null;
        if (!empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];

            if (!is_uploaded_file($file['tmp_name'])) {
                $newLogoError = 'Fail tidak sah.';
            } elseif ((int) $file['size'] > self::MAX_BYTES) {
                $newLogoError = 'Saiz logo melebihi had 2 MB.';
            } else {
                $ext  = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
                $mime = mime_content_type($file['tmp_name']) ?: '';
                if (!in_array($ext, self::ALLOWED_EXT, true) || !in_array($mime, self::ALLOWED_MIME, true)) {
                    $newLogoError = 'Jenis fail tidak dibenarkan (png, jpg, svg, webp).';
                } else {
                    $dir = APP_ROOT . '/storage/uploads/brand/';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                    $storedName = 'logo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $dest = $dir . $storedName;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $newLogoError = 'Gagal menyimpan logo.';
                    } else {
                        if ($currentLogo && $currentLogo !== $storedName) {
                            $this->deleteBrandFile($currentLogo, 'logo');
                        }
                        $this->settings->set('logo_path', $storedName);
                    }
                }
            }
        }

        // Handle payment QR upload (system-wide default).
        $newQrError = null;
        if (!empty($_FILES['payment_qr']) && ($_FILES['payment_qr']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $file = $_FILES['payment_qr'];

            if (!is_uploaded_file($file['tmp_name'])) {
                $newQrError = 'Fail QR tidak sah.';
            } elseif ((int) $file['size'] > self::MAX_BYTES) {
                $newQrError = 'Saiz QR melebihi had 2 MB.';
            } else {
                $ext  = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
                $mime = mime_content_type($file['tmp_name']) ?: '';
                if (!in_array($ext, self::ALLOWED_EXT, true) || !in_array($mime, self::ALLOWED_MIME, true)) {
                    $newQrError = 'Jenis fail QR tidak dibenarkan (png, jpg, svg, webp).';
                } else {
                    $dir = APP_ROOT . '/storage/uploads/brand/';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                    $storedName = 'qr_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $dest = $dir . $storedName;
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $newQrError = 'Gagal menyimpan QR.';
                    } else {
                        if ($currentQr && $currentQr !== $storedName) {
                            $this->deleteBrandFile($currentQr, 'qr');
                        }
                        $this->settings->set('payment_qr_path', $storedName);
                    }
                }
            }
        }

        AuditService::log('settings.updated', (int) ($_SESSION['user_id'] ?? 0), 'app_settings', 0, [
            'app_name_changed' => true,
            'logo_changed'     => !empty($_FILES['logo']['tmp_name'] ?? '') && $newLogoError === null,
            'logo_removed'     => $removeLogo,
            'qr_changed'       => !empty($_FILES['payment_qr']['tmp_name'] ?? '') && $newQrError === null,
            'qr_removed'       => $removeQr,
        ]);

        if ($newLogoError !== null || $newQrError !== null) {
            $messages = [];
            if ($newLogoError !== null) { $messages[] = 'logo: ' . $newLogoError; }
            if ($newQrError !== null)   { $messages[] = 'QR: ' . $newQrError; }
            set_flash('warning', 'Tetapan lain disimpan, tetapi ' . implode('; ', $messages));
        } else {
            set_flash('success', 'Tetapan sistem berjaya dikemaskini.');
        }
        $this->redirect('/admin/settings');
    }

    /**
     * Delete a brand asset (logo or QR) from disk. Failures are swallowed —
     * they should not block the user from saving new settings.
     */
    private function deleteBrandFile(string $storedName, string $kind): void
    {
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $storedName)) {
            return;
        }
        $prefix = $kind === 'qr' ? 'qr_' : 'logo_';
        if (!str_starts_with($storedName, $prefix)) {
            return;
        }
        $path = APP_ROOT . '/storage/uploads/brand/' . $storedName;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
