<?php
/**
 * @var array $settings
 * @var array $systemConfig
 * @var array $blasts
 * @var int   $blastCount
 */

$appName    = $settings['app_name']    ?? 'Sistem Pengurusan Main Kutu';
$tagline    = $settings['brand_tagline'] ?? '';
$logoPath   = $settings['logo_path']   ?? null;
$qrPath     = $settings['payment_qr_path'] ?? null;

// System settings (cast by SystemSettingService).
$emailBlastEnabled = !empty($systemConfig['email_blast_enabled']);
$emailBlastFromName = (string) ($systemConfig['email_blast_from_name'] ?? '');
$emailBlastFromEmail = (string) ($systemConfig['email_blast_from_email'] ?? '');
$emailBlastReplyTo = (string) ($systemConfig['email_blast_reply_to'] ?? '');
$emailBlastFooter = (string) ($systemConfig['email_blast_footer'] ?? '');
$emailBlastDefaultSubject = (string) ($systemConfig['email_blast_default_subject'] ?? '');

$wapnetEnabled = !empty($systemConfig['wapnet_enabled']);
$wapnetApiUrl = (string) ($systemConfig['wapnet_api_url'] ?? '');
$wapnetApiKey = (string) ($systemConfig['wapnet_api_key'] ?? '');
$wapnetSender = (string) ($systemConfig['wapnet_sender_id'] ?? '');
$wapnetTemplate = (string) ($systemConfig['wapnet_default_template'] ?? '');

$contactPhone = (string) ($systemConfig['system_contact_phone'] ?? '');
$contactEmail = (string) ($systemConfig['system_contact_email'] ?? '');

$statusTone = static function (string $s): string {
    return match ($s) {
        'sent'    => 'success',
        'partial' => 'warning',
        'failed'  => 'danger',
        default   => 'neutral',
    };
};
?>
<div class="page-header" style="margin-bottom: 2rem;">
    <div>
        <span class="page-eyebrow">Pentadbiran Sistem</span>
        <h1 class="page-title">Tetapan Sistem &amp; Integrasi</h1>
        <p class="page-subtitle muted">Urus konfigurasi jenama, email blast, gateway WhatsApp, keselamatan dan maklumat operasi.</p>
    </div>
</div>

<?= flash_messages() ?>

<?php if (empty($blastTableReady)): ?>
    <div class="alert alert-warning" role="alert" style="margin-bottom: 1.5rem;">
        <strong>Jadual <code>email_blasts</code> belum dicipta.</strong>
        Sejarah email blast tidak boleh dipaparkan. Jalankan migrasi <code>005_system_config.sql</code> untuk mengaktifkannya.
    </div>
<?php endif; ?>

<!-- Quick Status Cards Grid -->
<div class="grid grid-4" style="margin-bottom: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
    <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary, #3b82f6); background: var(--bg-surface, #fff);">
        <span class="muted small">Email Blaster</span>
        <div style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
            <strong style="font-size: 1.1rem;"><?= $emailBlastEnabled ? 'Aktif' : 'Dinyahaktif' ?></strong>
            <span class="badge badge-<?= $emailBlastEnabled ? 'success' : 'neutral' ?>"><?= $emailBlastEnabled ? 'ON' : 'OFF' ?></span>
        </div>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981; background: var(--bg-surface, #fff);">
        <span class="muted small">WhatsApp (wap.net)</span>
        <div style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
            <strong style="font-size: 1.1rem;"><?= $wapnetEnabled ? 'Aktif' : 'Dinyahaktif' ?></strong>
            <span class="badge badge-<?= $wapnetEnabled ? 'success' : 'neutral' ?>"><?= $wapnetEnabled ? 'ON' : 'OFF' ?></span>
        </div>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid #8b5cf6; background: var(--bg-surface, #fff);">
        <span class="muted small">Logo &amp; Penjenamaan</span>
        <div style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
            <strong style="font-size: 1.1rem;"><?= $logoPath ? 'Tersedia' : 'Lalai' ?></strong>
            <span class="badge badge-<?= $logoPath ? 'success' : 'neutral' ?>"><?= $logoPath ? 'Custom' : 'Default' ?></span>
        </div>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid #f59e0b; background: var(--bg-surface, #fff);">
        <span class="muted small">Sistem Keselamatan</span>
        <div style="margin-top: 0.5rem; display: flex; align-items: center; justify-content: space-between;">
            <strong style="font-size: 1.1rem;"><?= !empty($systemConfig['captcha_enabled']) ? 'CAPTCHA Aktif' : 'Standard' ?></strong>
            <span class="badge badge-<?= !empty($systemConfig['captcha_enabled']) ? 'success' : 'warning' ?>"><?= !empty($systemConfig['captcha_enabled']) ? 'Protected' : 'Standard' ?></span>
        </div>
    </div>
</div>

<!-- Modern Tab Navigation -->
<div class="settings-tabs" role="tablist" aria-label="Bahagian tetapan" style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border, #e2e8f0); margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 2px;">
    <a href="#tab-identity" class="settings-tab is-active" role="tab" aria-selected="true" data-tab="identity" style="padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px 6px 0 0;">1. Identiti &amp; QR</a>
    <a href="#tab-blaster" class="settings-tab" role="tab" aria-selected="false" data-tab="blaster" style="padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px 6px 0 0;">2. Email Blast</a>
    <a href="#tab-wapnet" class="settings-tab" role="tab" aria-selected="false" data-tab="wapnet" style="padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px 6px 0 0;">3. WhatsApp (wap.net)</a>
    <a href="#tab-operations" class="settings-tab" role="tab" aria-selected="false" data-tab="operations" style="padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px 6px 0 0;">4. Operasi &amp; Hubungan</a>
    <a href="#tab-security" class="settings-tab" role="tab" aria-selected="false" data-tab="security" style="padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px 6px 0 0;">5. Keselamatan &amp; CAPTCHA</a>
    <a href="#tab-database" class="settings-tab" role="tab" aria-selected="false" data-tab="database" style="padding: 0.75rem 1.25rem; font-weight: 600; text-decoration: none; border-radius: 6px 6px 0 0;">6. Pangkalan Data &amp; Integrasi</a>
</div>

<form method="POST" action="<?= url('/admin/settings') ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
        <!-- Main Form Column -->
        <div class="card" style="padding: 1.75rem; background: var(--bg-surface, #fff);">
            
            <!-- TAB: Identity / QR -->
            <section class="settings-pane is-active" data-pane="identity" aria-labelledby="tab-identity">
                <div style="margin-bottom: 1.5rem;">
                    <h2 class="card-heading" id="tab-identity" style="margin: 0; font-size: 1.25rem;">Identiti &amp; QR Pembayaran</h2>
                    <p class="muted small" style="margin-top: 0.25rem;">Nama platform, tagline, logo dan QR rasmi akaun pengurusan.</p>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="app_name" class="form-label" style="font-weight: 600;">Nama Sistem</label>
                    <input type="text" id="app_name" name="app_name" required maxlength="150" value="<?= e($appName) ?>" class="form-control" placeholder="cth: Sistem Pengurusan Main Kutu">
                    <p class="form-help muted small">Maksimum 150 aksara. Dipaparkan di header, footer dan emel sistem.</p>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="brand_tagline" class="form-label" style="font-weight: 600;">Tagline Penjenamaan</label>
                    <input type="text" id="brand_tagline" name="brand_tagline" maxlength="200" value="<?= e($tagline) ?>" class="form-control" placeholder="Tagline ringkas sistem">
                    <p class="form-help muted small">Dipaparkan pada halaman utama dan penerangan sistem.</p>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="logo" class="form-label" style="font-weight: 600;">Muat Naik Logo Platform</label>
                    <div style="display: flex; gap: 1.25rem; align-items: center; margin-top: 0.5rem; flex-wrap: wrap;">
                        <div style="width: 80px; height: 80px; border-radius: 8px; border: 1px dashed var(--border, #cbd5e1); display: flex; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden;">
                            <?php if ($logoPath): ?>
                                <img src="<?= url('/brand/logo') ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <strong style="color: #64748b; font-size: 1.25rem;">MK</strong>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; min-width: 220px;">
                            <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="form-control">
                            <p class="form-help muted small" style="margin-top: 0.25rem;">Format: PNG, JPG, SVG, WEBP. Maksimum 2 MB.</p>
                            <?php if ($logoPath): ?>
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="remove_logo" value="1">
                                    <span class="small text-danger" style="color: #dc2626;">Padam logo semasa (guna logo lalai)</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="payment_qr" class="form-label" style="font-weight: 600;">QR Pembayaran Utama (Sistem)</label>
                    <p class="form-help muted small" style="margin-top: 0;">QR kod lalai untuk pembayaran ahli yang tiada kod QR khusus pada pelan.</p>
                    <div style="display: flex; gap: 1.25rem; align-items: center; margin-top: 0.5rem; flex-wrap: wrap;">
                        <div style="width: 100px; height: 100px; border-radius: 8px; border: 1px dashed var(--border, #cbd5e1); display: flex; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden;">
                            <?php if ($qrPath): ?>
                                <img src="<?= url('/brand/qr') ?>" alt="QR" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <span class="muted small" style="text-align: center; font-size: 0.75rem;">Tiada QR</span>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; min-width: 220px;">
                            <input type="file" id="payment_qr" name="payment_qr" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="form-control">
                            <p class="form-help muted small" style="margin-top: 0.25rem;">Format: PNG, JPG, SVG, WEBP. Maksimum 2 MB.</p>
                            <?php if ($qrPath): ?>
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="remove_qr" value="1">
                                    <span class="small text-danger" style="color: #dc2626;">Padam QR sistem semasa</span>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TAB: Email blast -->
            <section class="settings-pane" data-pane="blaster" aria-labelledby="tab-blaster" hidden>
                <div style="margin-bottom: 1.5rem;">
                    <h2 class="card-heading" id="tab-blaster" style="margin: 0; font-size: 1.25rem;">Konfigurasi Email Blast</h2>
                    <p class="muted small" style="margin-top: 0.25rem;">Tetapan penghantaran emel pengumuman pukal kepada ahli dan pentadbir.</p>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" name="email_blast_enabled" value="1" <?= $emailBlastEnabled ? 'checked' : '' ?> style="width: 1.15rem; height: 1.15rem;">
                        <div>
                            <strong>Aktifkan Fungsi Email Blast</strong>
                            <div class="muted small">Benarkan pentadbir menghantar emel siaran terus dari panel admin.</div>
                        </div>
                    </label>
                </div>

                <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label for="email_blast_from_name" class="form-label" style="font-weight: 600;">Nama Pengirim</label>
                        <input type="text" id="email_blast_from_name" name="email_blast_from_name" value="<?= e($emailBlastFromName) ?>" maxlength="100" class="form-control" placeholder="cth: Pengurusan Main Kutu">
                    </div>
                    <div>
                        <label for="email_blast_from_email" class="form-label" style="font-weight: 600;">Emel Pengirim (From)</label>
                        <input type="email" id="email_blast_from_email" name="email_blast_from_email" value="<?= e($emailBlastFromEmail) ?>" maxlength="150" class="form-control" placeholder="no-reply@sistemkutu.my">
                    </div>
                </div>

                <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label for="email_blast_reply_to" class="form-label" style="font-weight: 600;">Reply-To (Pilihan)</label>
                        <input type="email" id="email_blast_reply_to" name="email_blast_reply_to" value="<?= e($emailBlastReplyTo) ?>" maxlength="150" class="form-control" placeholder="sokongan@sistemkutu.my">
                    </div>
                    <div>
                        <label for="email_blast_default_subject" class="form-label" style="font-weight: 600;">Subjek Lalai</label>
                        <input type="text" id="email_blast_default_subject" name="email_blast_default_subject" value="<?= e($emailBlastDefaultSubject) ?>" maxlength="200" class="form-control" placeholder="Notis Rasmi Sistem Kutu">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="email_blast_footer" class="form-label" style="font-weight: 600;">Kaki Surat Emel (Footer)</label>
                    <textarea id="email_blast_footer" name="email_blast_footer" rows="3" maxlength="800" class="form-control" placeholder="cth: Sekian, Terima Kasih&#10;Pasukan Pengurusan"><?= e($emailBlastFooter) ?></textarea>
                </div>
            </section>

            <!-- TAB: wap.net (WhatsApp Gateway) -->
            <section class="settings-pane" data-pane="wapnet" aria-labelledby="tab-wapnet" hidden>
                <div style="margin-bottom: 1.5rem;">
                    <h2 class="card-heading" id="tab-wapnet" style="margin: 0; font-size: 1.25rem;">Integrasi WhatsApp Gateway (wap.net)</h2>
                    <p class="muted small" style="margin-top: 0.25rem;">Konfigurasi API wap.net untuk penghantaran mesej dan notifikasi giliran/bayaran WhatsApp automatik.</p>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem; background: #f0fdf4; padding: 1rem; border-radius: 8px; border: 1px solid #bbf7d0;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" name="wapnet_enabled" value="1" <?= $wapnetEnabled ? 'checked' : '' ?> style="width: 1.15rem; height: 1.15rem;">
                        <div>
                            <strong>Aktifkan Integrasi wap.net</strong>
                            <div class="muted small" style="color: #166534;">Benarkan sistem memanggil API wap.net untuk notifikasi terus ke WhatsApp pengguna.</div>
                        </div>
                    </label>
                </div>

                <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label for="wapnet_api_url" class="form-label" style="font-weight: 600;">API Endpoint URL</label>
                        <input type="url" id="wapnet_api_url" name="wapnet_api_url" value="<?= e($wapnetApiUrl) ?>" maxlength="255" class="form-control" placeholder="https://api.wap.net/v1/messages">
                    </div>
                    <div>
                        <label for="wapnet_sender_id" class="form-label" style="font-weight: 600;">Sender / Instance ID</label>
                        <input type="text" id="wapnet_sender_id" name="wapnet_sender_id" value="<?= e($wapnetSender) ?>" maxlength="100" class="form-control" placeholder="cth: 60123456789">
                    </div>
                </div>

                <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label for="wapnet_api_key" class="form-label" style="font-weight: 600;">API Key / Secret Token</label>
                        <input type="password" id="wapnet_api_key" name="wapnet_api_key" value="<?= e($wapnetApiKey) ?>" maxlength="255" class="form-control" placeholder="Token rahsia dari portal wap.net" autocomplete="off">
                        <p class="form-help muted small">Token disulitkan dan dilindungi dengan selamat.</p>
                    </div>
                    <div>
                        <label for="wapnet_default_template" class="form-label" style="font-weight: 600;">Kod Templat Lalai</label>
                        <input type="text" id="wapnet_default_template" name="wapnet_default_template" value="<?= e($wapnetTemplate) ?>" maxlength="100" class="form-control" placeholder="general_notification">
                    </div>
                </div>
            </section>

            <!-- TAB: Operations / contact -->
            <section class="settings-pane" data-pane="operations" aria-labelledby="tab-operations" hidden>
                <div style="margin-bottom: 1.5rem;">
                    <h2 class="card-heading" id="tab-operations" style="margin: 0; font-size: 1.25rem;">Operasi &amp; Maklumat Hubungan</h2>
                    <p class="muted small" style="margin-top: 0.25rem;">Maklumat perhubungan pentadbiran dan helpdesk untuk kemudahan ahli.</p>
                </div>

                <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label for="system_contact_phone" class="form-label" style="font-weight: 600;">Nombor Telefon Helpdesk</label>
                        <input type="text" id="system_contact_phone" name="system_contact_phone" value="<?= e($contactPhone) ?>" maxlength="30" class="form-control" placeholder="03-XXXXXXXX atau 012-XXXXXXX">
                    </div>
                    <div>
                        <label for="system_contact_email" class="form-label" style="font-weight: 600;">Emel Khidmat Pelanggan</label>
                        <input type="email" id="system_contact_email" name="system_contact_email" value="<?= e($contactEmail) ?>" maxlength="150" class="form-control" placeholder="helpdesk@sistemkutu.my">
                    </div>
                </div>
            </section>

            <!-- TAB: Security & CAPTCHA -->
            <section class="settings-pane" data-pane="security" aria-labelledby="tab-security" hidden>
                <div style="margin-bottom: 1.5rem;">
                    <h2 class="card-heading" id="tab-security" style="margin: 0; font-size: 1.25rem;">Keselamatan &amp; Kawalan CAPTCHA</h2>
                    <p class="muted small" style="margin-top: 0.25rem;">Perlindungan daripada serangan brute-force, bot dan pendaftaran spam.</p>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem; background: #fff7ed; padding: 1rem; border-radius: 8px; border: 1px solid #fed7aa;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="checkbox" id="captcha_enabled" name="captcha_enabled" value="1" <?= !empty($systemConfig['captcha_enabled']) ? 'checked' : '' ?> style="width: 1.15rem; height: 1.15rem;">
                        <div>
                            <strong>Aktifkan Sistem CAPTCHA Global</strong>
                            <div class="muted small" style="color: #9a3412;">Menguatkuasakan verifikasi soalan matematik / bot pada halaman sensitif.</div>
                        </div>
                    </label>
                </div>

                <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Tetapan Perlindungan Per-Halaman</h3>
                <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="captcha_on_login" value="1" <?= !empty($systemConfig['captcha_on_login']) ? 'checked' : '' ?>>
                        <span>Halaman Log Masuk</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="captcha_on_register" value="1" <?= !empty($systemConfig['captcha_on_register']) ? 'checked' : '' ?>>
                        <span>Pendaftaran Akaun Ahli</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="captcha_on_forgot_password" value="1" <?= !empty($systemConfig['captcha_on_forgot_password']) ? 'checked' : '' ?>>
                        <span>Lupa Kata Laluan</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="captcha_on_reset_password" value="1" <?= !empty($systemConfig['captcha_on_reset_password']) ? 'checked' : '' ?>>
                        <span>Penetapan Semula Kata Laluan</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="captcha_on_admin_blast" value="1" <?= !empty($systemConfig['captcha_on_admin_blast']) ? 'checked' : '' ?>>
                        <span>Borang Email Blast Admin</span>
                    </label>
                </div>

                <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">Konfigurasi AWS WAF CAPTCHA (Pilihan Tambahan)</h3>
                <p class="muted small" style="margin-bottom: 1rem;">Diperlukan hanya jika menggunakan integrasi AWS WAF Web ACL.</p>

                <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label for="aws_waf_api_key" class="form-label" style="font-weight: 600;">AWS WAF API Key</label>
                        <input type="password" id="aws_waf_api_key" name="aws_waf_api_key" value="<?= e((string) ($systemConfig['aws_waf_api_key'] ?? '')) ?>" class="form-control" placeholder="API key AWS WAF" autocomplete="off">
                    </div>
                    <div>
                        <label for="aws_waf_secret_key" class="form-label" style="font-weight: 600;">AWS WAF Secret Key</label>
                        <input type="password" id="aws_waf_secret_key" name="aws_waf_secret_key" value="<?= e((string) ($systemConfig['aws_waf_secret_key'] ?? '')) ?>" class="form-control" placeholder="Secret Key" autocomplete="off">
                    </div>
                </div>
            </section>

            <!-- TAB: Database & Integration Inspector -->
            <section class="settings-pane" data-pane="database" aria-labelledby="tab-database" hidden>
                <div style="margin-bottom: 1.5rem;">
                    <h2 class="card-heading" id="tab-database" style="margin: 0; font-size: 1.25rem;">Pangkalan Data &amp; Konfigurasi Integrasi</h2>
                    <p class="muted small" style="margin-top: 0.25rem;">Eksport sandaran penuh pangkalan data, import semula fail SQL, dan semak inventori jadual sistem.</p>
                </div>

                <!-- DB Export & Import Action Cards -->
                <div class="grid grid-2" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
                    <!-- Export Card -->
                    <div class="card" style="padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <div style="width: 40px; height: 40px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="7 10 12 15 17 10"/>
                                        <line x1="12" y1="15" x2="12" y2="3"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: #0f172a;">Eksport Pangkalan Data (SQL)</h3>
                                    <p class="muted small" style="margin: 0;">Muat turun salinan sandaran skema &amp; rekod data.</p>
                                </div>
                            </div>
                            <p class="muted small" style="margin-bottom: 1.25rem; line-height: 1.5;">
                                Menghasilkan fail SQL lengkap mengandungi semua struktur jadual dan data terkini. Fail ini boleh disimpan sebagai sandaran atau digunakan untuk migrasi.
                            </p>
                        </div>
                        <a href="<?= url('/admin/settings/database/export') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            <span>Muat Turun Eksport SQL</span>
                        </a>
                    </div>

                    <!-- Import Card -->
                    <div class="card" style="padding: 1.25rem; border: 1px solid #fed7aa; border-radius: 8px; background: #fffaf5; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <div style="width: 40px; height: 40px; border-radius: 8px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 600; color: #9a3412;">Import Pangkalan Data (SQL)</h3>
                                    <p class="muted small" style="margin: 0; color: #c2410c;">Pulihkan data dari fail sandaran <code>.sql</code></p>
                                </div>
                            </div>
                            <p class="small" style="color: #7c2d12; margin-bottom: 1rem; line-height: 1.5;">
                                <strong>Amaran Keselamatan:</strong> Memasukkan fail SQL akan mengubah atau menggantikan rekod pangkalan data semasa. Pastikan sandaran telah dibuat terlebih dahulu.
                            </p>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('import-db-form-wrapper').style.display = document.getElementById('import-db-form-wrapper').style.display === 'none' ? 'block' : 'none';" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <span>Buka Borang Import SQL</span>
                        </button>
                    </div>
                </div>

                <!-- Hidden Collapsible Import Form -->
                <div id="import-db-form-wrapper" style="display: none; margin-bottom: 2rem; padding: 1.5rem; background: #ffffff; border: 2px dashed #f97316; border-radius: 8px;">
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: #9a3412; font-weight: 600;">Muat Naik Fail SQL Untuk Dipulihkan</h3>
                    <p class="muted small" style="margin-bottom: 1rem;">Pilih fail <code>.sql</code> (saiz maksimum: 25MB). Semua arahan SQL akan dijalankan dalam transaksi selamat.</p>
                    
                    <form action="<?= url('/admin/settings/database/import') ?>" method="POST" enctype="multipart/form-data" onsubmit="return confirm('AMARAN: Adakah anda pasti mahu mengimport fail SQL ini ke dalam pangkalan data? Rekod sedia ada mungkin terjejas.');">
                        <?= csrf_field() ?>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="sql_file" class="form-label" style="font-weight: 600;">Pilih Fail SQL (.sql)</label>
                            <input type="file" id="sql_file" name="sql_file" accept=".sql,text/plain" required class="form-control" style="padding: 0.5rem;">
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('import-db-form-wrapper').style.display='none';">Batal</button>
                            <button type="submit" class="btn btn-danger" style="background: #dc2626; color: #fff;">Mulakan Import SQL Sekarang</button>
                        </div>
                    </form>
                </div>

                <?php
                $catLabel = static fn (string $c): string => match ($c) {
                    'auth'    => 'Pengesahan & Akses',
                    'members' => 'Ahli & Skor Kredit',
                    'plans'   => 'Pelan & Jadual',
                    'finance' => 'Kewangan & Transaksi',
                    'system'  => 'Tetapan Sistem',
                    default   => 'Lain-lain',
                };
                $catTone = static fn (string $c): string => match ($c) {
                    'auth'    => '#3b82f6',
                    'members' => '#10b981',
                    'plans'   => '#8b5cf6',
                    'finance' => '#f59e0b',
                    'system'  => '#64748b',
                    default   => '#94a3b8',
                };
                $groupedTables = [];
                foreach (($dbTables ?? []) as $tbl) {
                    $groupedTables[$tbl['category']][] = $tbl;
                }
                ?>

                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem 0;">Inventori Jadual Pangkalan Data</h3>
                    <?php if (empty($dbTables)): ?>
                        <div class="alert alert-warning">Tidak dapat membaca skema pangkalan data. Pastikan pengguna DB mempunyai akses <code>information_schema</code>.</div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($groupedTables as $cat => $tbls): ?>
                                <div class="card" style="padding: 1rem 1.25rem; border-left: 4px solid <?= $catTone($cat) ?>;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <strong style="color: <?= $catTone($cat) ?>;"><?= e($catLabel($cat)) ?></strong>
                                        <span class="badge badge-neutral"><?= count($tbls) ?> jadual</span>
                                    </div>
                                    <div class="table-wrap" style="max-height: 280px; overflow-y: auto;">
                                        <table class="table" style="font-size: 0.85rem;">
                                            <thead>
                                                <tr>
                                                    <th>Nama Jadual</th>
                                                    <th style="text-align:right;">Anggaran Baris</th>
                                                    <th style="text-align:right;">Kolum</th>
                                                    <th>Skema</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tbls as $tbl): ?>
                                                    <tr>
                                                        <td><code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;"><?= e($tbl['name']) ?></code></td>
                                                        <td style="text-align:right;"><?= number_format((int) $tbl['rows']) ?></td>
                                                        <td style="text-align:right;"><?= count($tbl['columns']) ?></td>
                                                        <td>
                                                            <?php foreach (array_slice($tbl['columns'], 0, 6) as $col): ?>
                                                                <span class="badge badge-neutral" style="margin: 1px 2px; font-size: 0.7rem;" title="<?= e($col['name']) ?> &raquo; <?= e($col['type']) ?>"><?= e($col['name']) ?></span>
                                                            <?php endforeach; ?>
                                                            <?php if (count($tbl['columns']) > 6): ?>
                                                                <span class="muted small">+<?= count($tbl['columns']) - 6 ?> lagi</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 style="font-size: 1rem; font-weight: 600; margin: 0 0 0.75rem 0;">Konfigurasi Interasi Aktif (Live)</h3>
                <p class="muted small" style="margin-bottom: 1rem;">Nilai yang sedang digunakan oleh sistem pada masa runtime.</p>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Tetapan</th>
                                <th>Kunci</th>
                                <th>Nilai</th>
                                <th>Jenis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $lastGroup = null;
                            foreach (($integrations ?? []) as $i):
                                $valTone = match (true) {
                                    $i['type'] === 'bool' && $i['value'] === '1' => 'success',
                                    $i['type'] === 'bool' => 'neutral',
                                    $i['type'] === 'secret' && $i['value'] !== '' => 'info',
                                    $i['value'] === '' || $i['value'] === null => 'warning',
                                    default => 'neutral',
                                };
                            ?>
                                <tr>
                                    <td><?php if ($i['group'] !== $lastGroup): ?><span class="badge badge-primary"><?= e($i['group']) ?></span><?php $lastGroup = $i['group']; endif; ?></td>
                                    <td><strong><?= e($i['label']) ?></strong></td>
                                    <td><code style="font-size:0.78rem;"><?= e($i['key']) ?></code></td>
                                    <td>
                                        <span class="badge badge-<?= $valTone ?>"><?= e($i['display']) ?></span>
                                    </td>
                                    <td><span class="muted small"><?= e($i['type']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <?= captcha_field('admin_settings') ?>

            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; display: flex; gap: 0.75rem; justify-content: flex-end;">
                <a href="<?= url('/admin') ?>" class="btn btn-secondary">Batal / Kembali</a>
                <button type="submit" class="btn btn-primary" style="min-width: 160px;">Simpan Semua Tetapan</button>
            </div>

        </div>

        <!-- Sidebar Preview Column -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="card" style="padding: 1.5rem; background: var(--bg-surface, #fff);">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 600;">Pratonton Jenama</h3>
                <p class="muted small" style="margin-bottom: 1rem;">Paparan identiti semasa pada sistem.</p>
                
                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="width: 48px; height: 48px; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <?php if ($logoPath): ?>
                            <img src="<?= url('/brand/logo') ?>" alt="" style="max-width: 100%; max-height: 100%;">
                        <?php else: ?>
                            <strong style="color: var(--primary, #2563eb);">MK</strong>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #1e293b;"><?= e($appName) ?></div>
                        <?php if ($tagline): ?>
                            <div class="muted small"><?= e($tagline) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($qrPath): ?>
                    <div style="margin-top: 1rem; text-align: center; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <img src="<?= url('/brand/qr') ?>" alt="QR Sistem" style="max-width: 140px; border-radius: 6px;">
                        <div class="muted small" style="margin-top: 0.5rem; font-weight: 600;">QR Pembayaran Sistem</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card" style="padding: 1.5rem; background: var(--bg-surface, #fff);">
                <h3 style="margin: 0 0 0.75rem 0; font-size: 1.1rem; font-weight: 600;">Ringkasan Status Integrasi</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="small">Email Blaster:</span>
                        <span class="badge badge-<?= $emailBlastEnabled ? 'success' : 'neutral' ?>"><?= $emailBlastEnabled ? 'Aktif' : 'Mati' ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="small">wap.net (WhatsApp):</span>
                        <span class="badge badge-<?= $wapnetEnabled ? 'success' : 'neutral' ?>"><?= $wapnetEnabled ? 'Aktif' : 'Mati' ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="small">Perlindungan Bot:</span>
                        <span class="badge badge-<?= !empty($systemConfig['captcha_enabled']) ? 'success' : 'neutral' ?>"><?= !empty($systemConfig['captcha_enabled']) ? 'Aktif' : 'Standard' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Email Blast Composer & History Section -->
<div class="card" style="margin-top: 2.5rem; padding: 1.75rem; background: var(--bg-surface, #fff);">
    <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem;">
        <span class="page-eyebrow" style="color: var(--primary, #2563eb);">Pusat Komunikasi</span>
        <h2 style="margin: 0.25rem 0 0; font-size: 1.35rem;">Hantar Email Blast Segera</h2>
        <p class="muted small" style="margin-top: 0.25rem;">Hantar notis atau pengumuman rasmi terus ke peti masuk emel pengguna.</p>
    </div>

    <?php if (!$emailBlastEnabled): ?>
        <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
            Email blast belum diaktifkan. Sila tandakan <strong>Aktifkan Fungsi Email Blast</strong> di Tab 2 di atas dan klik Simpan.
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/settings/blast') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="blast_subject" class="form-label" style="font-weight: 600;">Tajuk / Subjek Emel</label>
            <input type="text" id="blast_subject" name="subject" required maxlength="200" value="<?= e($emailBlastDefaultSubject) ?>" class="form-control" placeholder="Subjek emel pengumuman">
        </div>
        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="blast_message" class="form-label" style="font-weight: 600;">Kandungan Mesej</label>
            <textarea id="blast_message" name="message" required rows="5" maxlength="4000" class="form-control" placeholder="Tulis pengumuman lengkap anda di sini..."></textarea>
        </div>

        <?= captcha_field('admin_blast') ?>

        <div style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 240px;">
                <label for="blast_target" class="form-label" style="font-weight: 600;">Kumpulan Sasaran Penerima</label>
                <select id="blast_target" name="target_role" class="form-control">
                    <option value="all">Semua Pengguna (Ahli &amp; Pentadbir)</option>
                    <option value="member">Ahli Kutu Sahaja</option>
                    <option value="admin">Semua Pentadbir (Admin &amp; Staff)</option>
                    <option value="super_admin">Super Admin Sahaja</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" <?= $emailBlastEnabled ? '' : 'disabled' ?> style="min-width: 160px; height: 42px;">
                Hantar Sekarang &rarr;
            </button>
        </div>
    </form>

    <?php if (($blastCount ?? 0) > 0): ?>
        <div style="margin-top: 2rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Log Penghantaran Email Blast</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tarikh &amp; Masa</th>
                            <th>Subjek</th>
                            <th>Sasaran</th>
                            <th>Penerima</th>
                            <th>Status</th>
                            <th>Dihantar Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blasts as $b): ?>
                            <tr>
                                <td><?= e($b['sent_at'] ?: $b['created_at']) ?></td>
                                <td><strong><?= e(mb_strimwidth($b['subject'], 0, 60, '…')) ?></strong></td>
                                <td><span class="badge badge-neutral"><?= e($b['target_role']) ?></span></td>
                                <td><?= e((string) $b['recipient_count']) ?> orang</td>
                                <td><span class="badge badge-<?= $statusTone($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                                <td><?= e($b['creator_name'] ?? ('#' . $b['created_by'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
