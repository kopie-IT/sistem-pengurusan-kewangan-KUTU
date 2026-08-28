<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AppSettingRepository;
use App\Repositories\EmailBlastRepository;
use App\Services\AuditService;
use App\Services\EmailBlastService;
use App\Services\SystemSettingService;

/**
 * Admin-only configuration hub.
 *
 * - Identity: app name, tagline, logo, system QR (persisted via `app_settings`).
 * - Email blast toggle + From/Reply-To/Footer config + broadcast composer.
 * - wap.net (WhatsApp gateway) toggle + API URL/key/sender/template.
 * - General operation contact info.
 *
 * All settings live in `system_settings` so they can be queried cheaply from
 * other services (e.g. notifications, mailers, contact pages).
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
        private AppSettingRepository   $appSettings,
        private SystemSettingService  $systemSettings,
        private EmailBlastService     $blaster,
        private EmailBlastRepository  $blasts,
    ) {}

    public function index(): void
    {
        $this->view('admin/settings', [
            'title'         => 'Tetapan Sistem',
            'settings'      => $this->appSettings->all(),
            'systemConfig'  => $this->systemSettings->all(),
            'blasts'        => $this->blasts->all(20, 0),
            'blastCount'    => $this->blasts->count(),
        ]);
    }

    public function update(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/settings');
        }

        // ----- Identity (text fields) ---------------------------------
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

        $currentLogo = $this->appSettings->get('logo_path');
        $currentQr   = $this->appSettings->get('payment_qr_path');

        $this->appSettings->set('app_name', $appName);
        $this->appSettings->set('brand_tagline', $tagline !== '' ? $tagline : null);

        if ($removeLogo && $currentLogo) {
            $this->deleteBrandFile($currentLogo, 'logo');
            $this->appSettings->set('logo_path', null);
            $currentLogo = null;
        }

        if ($removeQr && $currentQr) {
            $this->deleteBrandFile($currentQr, 'qr');
            $this->appSettings->set('payment_qr_path', null);
            $currentQr = null;
        }

        // ----- Logo / QR upload (unchanged behaviour) -----------------
        $newLogoError = $this->handleLogoUpload($currentLogo);
        $newQrError   = $this->handleQrUpload($currentQr);

        // ----- Email blast settings (system_settings) -----------------
        $emailBlast = [
            'email_blast_enabled'        => isset($_POST['email_blast_enabled']) ? '1' : '0',
            'email_blast_from_name'      => trim((string) ($_POST['email_blast_from_name'] ?? '')),
            'email_blast_from_email'     => trim((string) ($_POST['email_blast_from_email'] ?? '')),
            'email_blast_reply_to'       => trim((string) ($_POST['email_blast_reply_to'] ?? '')),
            'email_blast_footer'         => trim((string) ($_POST['email_blast_footer'] ?? '')),
            'email_blast_default_subject' => trim((string) ($_POST['email_blast_default_subject'] ?? '')),
        ];

        if ($emailBlast['email_blast_from_email'] !== '' && !filter_var($emailBlast['email_blast_from_email'], FILTER_VALIDATE_EMAIL)) {
            $emailBlast['email_blast_from_email'] = '';
            set_flash('warning', 'Emel pengirim blast tidak sah; nilai dikosongkan.');
        }
        if ($emailBlast['email_blast_reply_to'] !== '' && !filter_var($emailBlast['email_blast_reply_to'], FILTER_VALIDATE_EMAIL)) {
            $emailBlast['email_blast_reply_to'] = '';
            set_flash('warning', 'Emel reply-to tidak sah; nilai dikosongkan.');
        }
        foreach ($emailBlast as $k => $v) {
            $type = $k === 'email_blast_enabled' ? 'bool' : 'string';
            $this->systemSettings->set($k, $v, $type);
        }

        // ----- wap.net / WhatsApp gateway config -----------------------
        $wapnet = [
            'wapnet_enabled'          => isset($_POST['wapnet_enabled']) ? '1' : '0',
            'wapnet_api_url'          => trim((string) ($_POST['wapnet_api_url'] ?? '')),
            'wapnet_api_key'          => trim((string) ($_POST['wapnet_api_key'] ?? '')),
            'wapnet_sender_id'        => trim((string) ($_POST['wapnet_sender_id'] ?? '')),
            'wapnet_default_template' => trim((string) ($_POST['wapnet_default_template'] ?? '')),
        ];
        foreach ($wapnet as $k => $v) {
            $type = $k === 'wapnet_enabled' ? 'bool' : 'string';
            $this->systemSettings->set($k, $v, $type);
        }

        // ----- General contact info ------------------------------------
        $contacts = [
            'system_contact_phone' => trim((string) ($_POST['system_contact_phone'] ?? '')),
            'system_contact_email' => trim((string) ($_POST['system_contact_email'] ?? '')),
        ];
        if ($contacts['system_contact_email'] !== '' && !filter_var($contacts['system_contact_email'], FILTER_VALIDATE_EMAIL)) {
            $contacts['system_contact_email'] = '';
            set_flash('warning', 'Emel hubungan sistem tidak sah; nilai dikosongkan.');
        }
        foreach ($contacts as $k => $v) {
            $this->systemSettings->set($k, $v, 'string');
        }

        AuditService::log('settings.updated', (int) ($_SESSION['user_id'] ?? 0), 'app_settings', 0, [
            'app_name_changed' => true,
            'logo_changed'     => $newLogoError === null && !empty($_FILES['logo']['tmp_name'] ?? ''),
            'logo_removed'     => $removeLogo,
            'qr_changed'       => $newQrError === null && !empty($_FILES['payment_qr']['tmp_name'] ?? ''),
            'qr_removed'       => $removeQr,
            'email_blast'      => $emailBlast['email_blast_enabled'] === '1',
            'wapnet'           => $wapnet['wapnet_enabled'] === '1',
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
     * Handle the "Email Blast" form — sends a broadcast immediately and
     * records the result for audit/history. Routed via a separate action so
     * the heavy save flow above stays small.
     */
    public function sendBlast(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/settings');
        }

        if (!$this->systemSettings->get('email_blast_enabled', false)) {
            set_flash('error', 'Email blast belum diaktifkan. Aktifkan pada borang Tetapan dahulu.');
            $this->redirect('/admin/settings');
        }

        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $target  = (string) ($_POST['target_role'] ?? 'all');

        $result = $this->blaster->send(
            $subject,
            $message,
            $target,
            (int) ($_SESSION['user_id'] ?? 0)
        );

        if (!empty($result['ok'])) {
            set_flash('success', sprintf(
                'Email blast berjaya dihantar kepada %d penerima.',
                $result['count'] ?? 0
            ));
        } else {
            set_flash('error', $result['error'] ?? 'Gagal menghantar email blast.');
        }
        $this->redirect('/admin/settings');
    }

    private function handleLogoUpload(?string $currentLogo): ?string
    {
        if (empty($_FILES['logo']) || ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['logo'];
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Fail tidak sah.';
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            return 'Saiz logo melebihi had 2 MB.';
        }

        $ext  = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']) ?: '';
        if (!in_array($ext, self::ALLOWED_EXT, true) || !in_array($mime, self::ALLOWED_MIME, true)) {
            return 'Jenis fail tidak dibenarkan (png, jpg, svg, webp).';
        }

        $dir = APP_ROOT . '/storage/uploads/brand/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $storedName = 'logo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return 'Gagal menyimpan logo.';
        }

        if ($currentLogo && $currentLogo !== $storedName) {
            $this->deleteBrandFile($currentLogo, 'logo');
        }
        $this->appSettings->set('logo_path', $storedName);
        return null;
    }

    private function handleQrUpload(?string $currentQr): ?string
    {
        if (empty($_FILES['payment_qr']) || ($_FILES['payment_qr']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['payment_qr'];
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Fail QR tidak sah.';
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            return 'Saiz QR melebihi had 2 MB.';
        }

        $ext  = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']) ?: '';
        if (!in_array($ext, self::ALLOWED_EXT, true) || !in_array($mime, self::ALLOWED_MIME, true)) {
            return 'Jenis fail QR tidak dibenarkan (png, jpg, svg, webp).';
        }

        $dir = APP_ROOT . '/storage/uploads/brand/';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $storedName = 'qr_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return 'Gagal menyimpan QR.';
        }

        if ($currentQr && $currentQr !== $storedName) {
            $this->deleteBrandFile($currentQr, 'qr');
        }
        $this->appSettings->set('payment_qr_path', $storedName);
        return null;
    }

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
