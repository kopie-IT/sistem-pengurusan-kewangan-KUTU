<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AppSettingRepository;
use App\Repositories\EmailBlastRepository;
use App\Services\AuditService;
use App\Services\DatabaseBackupService;
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
        private \App\Services\CaptchaService $captcha,
    ) {}

    public function index(): void
    {
        $blastTableReady = $this->blasts->isTableReady();
        $blasts = $blastTableReady ? $this->blasts->all(20, 0) : [];
        $blastCount = $blastTableReady ? $this->blasts->count() : 0;

        $dbTables = $this->collectDatabaseInventory();
        $integrations = $this->collectIntegrationStatus();

        $this->view('admin/settings', [
            'title'             => 'Tetapan Sistem',
            'settings'          => $this->appSettings->all(),
            'systemConfig'      => $this->systemSettings->all(),
            'blasts'            => $blasts,
            'blastCount'        => $blastCount,
            'blastTableReady'   => $blastTableReady,
            'dbTables'          => $dbTables,
            'integrations'      => $integrations,
        ]);
    }

    /**
     * Export database to downloadable SQL file.
     */
    public function exportDatabase(): void
    {
        $backupService = new DatabaseBackupService();
        $sql = $backupService->exportSql();

        AuditService::log('database.export', (int) ($_SESSION['user_id'] ?? 0), 'system', null, [
            'bytes' => strlen($sql),
        ]);

        $filename = 'backup_kutu_' . date('Ymd_His') . '.sql';

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $sql;
        exit;
    }

    /**
     * Import database from uploaded SQL file.
     */
    public function importDatabase(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/settings#tab-database');
        }

        if (empty($_FILES['sql_file']) || ($_FILES['sql_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            set_flash('error', 'Sila pilih fail SQL yang sah untuk dimuat naik.');
            $this->redirect('/admin/settings#tab-database');
        }

        $file = $_FILES['sql_file'];
        if (!is_uploaded_file($file['tmp_name'])) {
            set_flash('error', 'Fail muat naik tidak sah.');
            $this->redirect('/admin/settings#tab-database');
        }

        // Limit SQL file size to 25MB
        if ((int) $file['size'] > 25 * 1024 * 1024) {
            set_flash('error', 'Saiz fail SQL melebihi had maksimum 25 MB.');
            $this->redirect('/admin/settings#tab-database');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            set_flash('error', 'Hanya fail berformat .sql dibenarkan untuk proses import.');
            $this->redirect('/admin/settings#tab-database');
        }

        $sqlContent = file_get_contents($file['tmp_name']);
        if ($sqlContent === false || trim($sqlContent) === '') {
            set_flash('error', 'Gagal membaca kandungan fail SQL.');
            $this->redirect('/admin/settings#tab-database');
        }

        $backupService = new DatabaseBackupService();
        $result = $backupService->importSql($sqlContent);

        if ($result['ok']) {
            AuditService::log('database.import', (int) ($_SESSION['user_id'] ?? 0), 'system', null, [
                'file_name' => $file['name'],
                'queries'   => $result['queriesExecuted'] ?? 0,
            ]);
            set_flash('success', sprintf('Pangkalan data berjaya diimport! %d pernyataan SQL dilaksanakan.', $result['queriesExecuted'] ?? 0));
        } else {
            set_flash('error', $result['error'] ?? 'Gagal memproses import fail SQL.');
        }

        $this->redirect('/admin/settings#tab-database');
    }

    public function update(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
            set_flash('error', 'Sesi tidak sah. Sila cuba lagi.');
            $this->redirect('/admin/settings');
        }

        if ($this->captcha->isRequiredOn('admin_settings')) {
            $answer = (string) ($_POST['captcha_answer_admin_settings'] ?? '');
            $capToken = (string) ($_POST['captcha_token_admin_settings']  ?? '');
            if (!$this->captcha->verify($answer, $capToken)) {
                set_flash('error', 'Pengesahan CAPTCHA gagal. Sila cuba lagi.');
                $this->redirect('/admin/settings');
            }
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

        // ----- CAPTCHA / AWS WAF settings ------------------------------
        $captchaToggles = [
            'captcha_enabled'              => isset($_POST['captcha_enabled']),
            'captcha_on_login'             => isset($_POST['captcha_on_login']),
            'captcha_on_register'          => isset($_POST['captcha_on_register']),
            'captcha_on_forgot_password'   => isset($_POST['captcha_on_forgot_password']),
            'captcha_on_reset_password'    => isset($_POST['captcha_on_reset_password']),
            'captcha_on_admin_blast'       => isset($_POST['captcha_on_admin_blast']),
        ];
        foreach ($captchaToggles as $k => $on) {
            $this->systemSettings->set($k, $on ? '1' : '0', 'bool');
        }

        $awsWaf = [
            'aws_waf_api_key'      => trim((string) ($_POST['aws_waf_api_key']      ?? '')),
            'aws_waf_secret_key'   => trim((string) ($_POST['aws_waf_secret_key']   ?? '')),
            'aws_waf_captcha_api'  => trim((string) ($_POST['aws_waf_captcha_api']  ?? '')),
            'aws_waf_captcha_js'   => trim((string) ($_POST['aws_waf_captcha_js']   ?? '')),
        ];
        // Empty fields clear the existing value (so re-saving with a blank
        // field actually wipes the key, instead of leaving the old one).
        foreach ($awsWaf as $k => $v) {
            $this->systemSettings->set($k, $v !== '' ? $v : null, 'string');
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

        if (!$this->blasts->isTableReady()) {
            set_flash('error', 'Jadual email_blasts belum dicipta. Jalankan migration 005_system_config.sql.');
            $this->redirect('/admin/settings');
        }

        if ($this->captcha->isRequiredOn('admin_blast')) {
            $answer = (string) ($_POST['captcha_answer_admin_blast'] ?? '');
            $token  = (string) ($_POST['captcha_token_admin_blast']  ?? '');
            if (!$this->captcha->verify($answer, $token)) {
                set_flash('error', 'Pengesahan CAPTCHA gagal. Sila cuba lagi.');
                $this->redirect('/admin/settings');
            }
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

    /**
     * Pull a live snapshot of the DB schema so the Sistem page can render the
     * tables & column inventory straight from the live database. Limited to
     * the current schema and grouped by category for readability.
     *
     * @return array<int, array{name: string, category: string, columns: array<int, array{name: string, type: string, nullable: bool, key: string, default: ?string}>}>
     */
    private function collectDatabaseInventory(): array
    {
        try {
            $pdo = \App\Core\Database::connection();
            $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

            $tablesStmt = $pdo->prepare(
                'SELECT TABLE_NAME, TABLE_ROWS
                   FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = :db
               ORDER BY TABLE_NAME ASC'
            );
            $tablesStmt->execute([':db' => $dbName]);
            $rawTables = $tablesStmt->fetchAll(\PDO::FETCH_ASSOC);

            $columnsStmt = $pdo->prepare(
                'SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE,
                        COLUMN_KEY, COLUMN_DEFAULT
                   FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = :db
               ORDER BY TABLE_NAME ASC, ORDINAL_POSITION ASC'
            );
            $columnsStmt->execute([':db' => $dbName]);
            $columnRows = $columnsStmt->fetchAll(\PDO::FETCH_ASSOC);

            $columnsByTable = [];
            foreach ($columnRows as $c) {
                $columnsByTable[$c['TABLE_NAME']][] = [
                    'name'     => (string) $c['COLUMN_NAME'],
                    'type'     => (string) $c['COLUMN_TYPE'],
                    'nullable' => ((string) $c['IS_NULLABLE']) === 'YES',
                    'key'      => (string) ($c['COLUMN_KEY'] ?? ''),
                    'default'  => $c['COLUMN_DEFAULT'] !== null ? (string) $c['COLUMN_DEFAULT'] : null,
                ];
            }

            $groups = [
                'auth'    => ['users', 'roles', 'user_roles', 'password_resets', 'sessions', 'audit_logs'],
                'members' => ['members', 'member_documents', 'credit_scores', 'credit_score_history', 'credit_score_rules'],
                'plans'   => ['plans', 'plan_members', 'plan_cycles', 'contribution_schedules', 'payout_schedules'],
                'finance' => ['payment_batches', 'payment_slips', 'payouts', 'withdrawals', 'ledger_transactions', 'shortfalls', 'fees'],
                'system'  => ['app_settings', 'system_settings', 'email_blasts', 'notifications', 'announcements'],
            ];

            $result = [];
            foreach ($rawTables as $t) {
                $name = (string) $t['TABLE_NAME'];
                $category = 'lain-lain';
                foreach ($groups as $cat => $tables) {
                    if (in_array($name, $tables, true)) {
                        $category = $cat;
                        break;
                    }
                }
                $result[] = [
                    'name'     => $name,
                    'category' => $category,
                    'rows'     => (int) ($t['TABLE_ROWS'] ?? 0),
                    'columns'  => $columnsByTable[$name] ?? [],
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Build a flat list of integration fields & their currently stored values
     * so the UI can render the actual config that the app is using at runtime.
     *
     * @return array<int, array{label: string, key: string, value: string, type: string, group: string}>
     */
    private function collectIntegrationStatus(): array
    {
        $cfg = $this->systemSettings->all();
        $app = $this->appSettings->all();

        $groups = [
            'Identiti' => [
                ['label' => 'Nama Sistem',         'key' => 'app_name',          'type' => 'string'],
                ['label' => 'Tagline Penjenamaan', 'key' => 'brand_tagline',     'type' => 'string'],
                ['label' => 'Logo Path',           'key' => 'logo_path',         'type' => 'file'],
                ['label' => 'QR Sistem Path',      'key' => 'payment_qr_path',   'type' => 'file'],
            ],
            'Email Blast' => [
                ['label' => 'Status',                'key' => 'email_blast_enabled',        'type' => 'bool'],
                ['label' => 'Nama Pengirim',         'key' => 'email_blast_from_name',      'type' => 'string'],
                ['label' => 'Emel Pengirim',         'key' => 'email_blast_from_email',     'type' => 'string'],
                ['label' => 'Reply-To',              'key' => 'email_blast_reply_to',       'type' => 'string'],
                ['label' => 'Subjek Lalai',          'key' => 'email_blast_default_subject', 'type' => 'string'],
            ],
            'WhatsApp (wap.net)' => [
                ['label' => 'Status',           'key' => 'wapnet_enabled',          'type' => 'bool'],
                ['label' => 'API Endpoint',     'key' => 'wapnet_api_url',          'type' => 'string'],
                ['label' => 'API Key',          'key' => 'wapnet_api_key',          'type' => 'secret'],
                ['label' => 'Sender ID',        'key' => 'wapnet_sender_id',        'type' => 'string'],
                ['label' => 'Templat Lalai',    'key' => 'wapnet_default_template', 'type' => 'string'],
            ],
            'Operasi & Hubungan' => [
                ['label' => 'Telefon Helpdesk', 'key' => 'system_contact_phone', 'type' => 'string'],
                ['label' => 'Emel Helpdesk',    'key' => 'system_contact_email', 'type' => 'string'],
            ],
            'Keselamatan / CAPTCHA' => [
                ['label' => 'CAPTCHA Global',          'key' => 'captcha_enabled',            'type' => 'bool'],
                ['label' => 'CAPTCHA Log Masuk',       'key' => 'captcha_on_login',           'type' => 'bool'],
                ['label' => 'CAPTCHA Pendaftaran',     'key' => 'captcha_on_register',        'type' => 'bool'],
                ['label' => 'CAPTCHA Lupa Password',   'key' => 'captcha_on_forgot_password', 'type' => 'bool'],
                ['label' => 'CAPTCHA Reset Password',  'key' => 'captcha_on_reset_password',  'type' => 'bool'],
                ['label' => 'CAPTCHA Email Blast',     'key' => 'captcha_on_admin_blast',     'type' => 'bool'],
                ['label' => 'AWS WAF API Key',         'key' => 'aws_waf_api_key',            'type' => 'secret'],
                ['label' => 'AWS WAF Secret',          'key' => 'aws_waf_secret_key',         'type' => 'secret'],
            ],
        ];

        $out = [];
        foreach ($groups as $groupName => $items) {
            foreach ($items as $item) {
                $key = $item['key'];
                $val = $app[$key] ?? ($cfg[$key] ?? null);
                $isTrue = in_array($val, ['1', 1, true, 'true'], true);
                $display = match ($item['type']) {
                    'bool'   => $isTrue ? 'Aktif' : 'Dinyahaktif',
                    'secret' => $val !== null && $val !== '' ? '••••••••' : '(kosong)',
                    'file'   => $val ? (string) $val : '(lalai)',
                    default  => $val !== null && $val !== '' ? (string) $val : '(kosong)',
                };
                $out[] = [
                    'group'  => $groupName,
                    'label'  => $item['label'],
                    'key'    => $key,
                    'value'  => (string) $val,
                    'type'   => $item['type'],
                    'display'=> $display,
                ];
            }
        }
        return $out;
    }
}
