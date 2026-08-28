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
<section class="page-header">
    <div>
        <span class="page-eyebrow">Pentadbiran</span>
        <h1 class="page-title">Tetapan Sistem</h1>
        <p class="page-subtitle">Urus identiti, komunikasi, integrasi, dan operasi sistem dari satu pusat kawalan.</p>
    </div>
</section>

<?= flash_messages() ?>

<div class="settings-tabs" role="tablist" aria-label="Bahagian tetapan">
    <a href="#tab-identity"   class="settings-tab is-active" role="tab" aria-selected="true" data-tab="identity">1. Identiti &amp; QR</a>
    <a href="#tab-blaster"    class="settings-tab" role="tab" aria-selected="false" data-tab="blaster">2. Email Blast</a>
    <a href="#tab-wapnet"     class="settings-tab" role="tab" aria-selected="false" data-tab="wapnet">3. wap.net (WhatsApp)</a>
    <a href="#tab-operations" class="settings-tab" role="tab" aria-selected="false" data-tab="operations">4. Operasi &amp; Hubungan</a>
</div>

<form method="POST" action="<?= url('/admin/settings') ?>" enctype="multipart/form-data" class="form-grid" novalidate>
    <?= csrf_field() ?>

    <div class="settings-grid">
        <div class="card">
            <div class="card-body">

                <!-- TAB: Identity / QR ---------------------------------------------- -->
                <section class="settings-pane is-active" data-pane="identity" aria-labelledby="tab-identity">
                    <h2 class="card-heading" id="tab-identity">Identiti &amp; QR Pembayaran</h2>
                    <p class="muted">Nama, tagline, logo, dan QR lalai untuk sistem &amp; ahli tanpa pelan khusus.</p>

                    <div class="form-group">
                        <label for="app_name" class="form-label">Nama Sistem</label>
                        <input type="text" id="app_name" name="app_name" required maxlength="150"
                               value="<?= e($appName) ?>" class="form-control"
                               placeholder="cth: Sistem Pengurusan Main Kutu">
                        <p class="form-help">Maksimum 150 aksara. Dipaparkan di tajuk halaman, header, dan footer.</p>
                    </div>

                    <div class="form-group">
                        <label for="brand_tagline" class="form-label">Tagline</label>
                        <input type="text" id="brand_tagline" name="brand_tagline" maxlength="200"
                               value="<?= e($tagline) ?>" class="form-control"
                               placeholder="Tagline ringkas untuk halaman awam">
                        <p class="form-help">Pilihan. Digunakan di footer dan halaman pemasaran.</p>
                    </div>

                    <div class="form-group">
                        <label for="logo" class="form-label">Logo</label>
                        <div class="logo-uploader">
                            <div class="logo-preview" aria-live="polite">
                                <?php if ($logoPath): ?>
                                    <img src="<?= url('/brand/logo') ?>" alt="Logo semasa">
                                    <small>Logo semasa</small>
                                <?php else: ?>
                                    <div class="logo-placeholder" aria-hidden="true">MK</div>
                                    <small>Tiada logo ditetapkan</small>
                                <?php endif; ?>
                            </div>
                            <div class="logo-fields">
                                <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                       class="form-control">
                                <p class="form-help">Format dibenarkan: PNG, JPG, SVG, WEBP. Maksimum 2 MB.</p>
                                <?php if ($logoPath): ?>
                                    <label class="checkbox-row">
                                        <input type="checkbox" name="remove_logo" value="1">
                                        <span>Buang logo semasa</span>
                                    </label>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_qr" class="form-label">QR Pembayaran (Sistem)</label>
                        <p class="form-help" style="margin-top:0;">QR lalai untuk ahli yang tiada pelan khusus. Setiap pelan boleh menukar QR sendiri.</p>
                        <div class="qr-uploader">
                            <div class="qr-preview" aria-live="polite">
                                <?php if ($qrPath): ?>
                                    <img src="<?= url('/brand/qr') ?>" alt="QR pembayaran semasa">
                                <?php else: ?>
                                    <span class="qr-empty">Tiada QR ditetapkan</span>
                                <?php endif; ?>
                            </div>
                            <div class="qr-fields">
                                <input type="file" id="payment_qr" name="payment_qr"
                                       accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                       class="form-control">
                                <p class="form-help">Format dibenarkan: PNG, JPG, SVG, WEBP. Maksimum 2 MB.</p>
                                <?php if ($qrPath): ?>
                                    <label class="checkbox-row">
                                        <input type="checkbox" name="remove_qr" value="1">
                                        <span>Buang QR semasa</span>
                                    </label>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- TAB: Email blast ---------------------------------------------- -->
                <section class="settings-pane" data-pane="blaster" aria-labelledby="tab-blaster" hidden>
                    <h2 class="card-heading" id="tab-blaster">Email Blast</h2>
                    <p class="muted">Aktifkan hantar emel pukal kepada ahli / pentadbir. Setiap blast akan direkodkan dalam log di bawah.</p>

                    <div class="form-group">
                        <label class="checkbox-row">
                            <input type="checkbox" name="email_blast_enabled" value="1" <?= $emailBlastEnabled ? 'checked' : '' ?>>
                            <span><strong>Aktifkan email blast</strong> &mdash; benarkan admin menghantar emel kepada pengguna.</span>
                        </label>
                    </div>

                    <div class="form-group form-grid form-grid-2">
                        <div>
                            <label for="email_blast_from_name" class="form-label">Nama pengirim</label>
                            <input type="text" id="email_blast_from_name" name="email_blast_from_name"
                                   value="<?= e($emailBlastFromName) ?>" maxlength="100"
                                   class="form-control" placeholder="Sistem Kutu">
                        </div>
                        <div>
                            <label for="email_blast_from_email" class="form-label">Emel pengirim</label>
                            <input type="email" id="email_blast_from_email" name="email_blast_from_email"
                                   value="<?= e($emailBlastFromEmail) ?>" maxlength="150"
                                   class="form-control" placeholder="no-reply@contoh.my">
                        </div>
                    </div>
                    <div class="form-group form-grid form-grid-2">
                        <div>
                            <label for="email_blast_reply_to" class="form-label">Reply-To (pilihan)</label>
                            <input type="email" id="email_blast_reply_to" name="email_blast_reply_to"
                                   value="<?= e($emailBlastReplyTo) ?>" maxlength="150"
                                   class="form-control" placeholder="sokongan@contoh.my">
                        </div>
                        <div>
                            <label for="email_blast_default_subject" class="form-label">Subjek lalai</label>
                            <input type="text" id="email_blast_default_subject" name="email_blast_default_subject"
                                   value="<?= e($emailBlastDefaultSubject) ?>" maxlength="200"
                                   class="form-control" placeholder="Notis Penting daripada Sistem Kutu">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email_blast_footer" class="form-label">Footer (pilihan)</label>
                        <textarea id="email_blast_footer" name="email_blast_footer" rows="3" maxlength="800"
                                  class="form-control"
                                  placeholder="cth: --&#10;Pasukan Sistem Kutu&#10;Hubungi: 03-XXXXXXX"><?= e($emailBlastFooter) ?></textarea>
                        <p class="form-help">Dipaparkan di akhir setiap blast.</p>
                    </div>
                </section>

                <!-- TAB: wap.net -------------------------------------------------- -->
                <section class="settings-pane" data-pane="wapnet" aria-labelledby="tab-wapnet" hidden>
                    <h2 class="card-heading" id="tab-wapnet">wap.net (WhatsApp Gateway)</h2>
                    <p class="muted">Konfigurasi integrasi wap.net untuk notifikasi WhatsApp. Tetapan ini akan digunakan oleh perkhidmatan notifikasi apabila diaktifkan.</p>

                    <div class="form-group">
                        <label class="checkbox-row">
                            <input type="checkbox" name="wapnet_enabled" value="1" <?= $wapnetEnabled ? 'checked' : '' ?>>
                            <span><strong>Aktifkan wap.net</strong> &mdash; benarkan sistem menghantar notifikasi WhatsApp.</span>
                        </label>
                    </div>

                    <div class="form-group form-grid form-grid-2">
                        <div>
                            <label for="wapnet_api_url" class="form-label">API URL</label>
                            <input type="url" id="wapnet_api_url" name="wapnet_api_url"
                                   value="<?= e($wapnetApiUrl) ?>" maxlength="255"
                                   class="form-control" placeholder="https://api.wap.net/v1/messages">
                        </div>
                        <div>
                            <label for="wapnet_sender_id" class="form-label">Sender ID</label>
                            <input type="text" id="wapnet_sender_id" name="wapnet_sender_id"
                                   value="<?= e($wapnetSender) ?>" maxlength="100"
                                   class="form-control" placeholder="cth: 60123456789">
                        </div>
                    </div>

                    <div class="form-group form-grid form-grid-2">
                        <div>
                            <label for="wapnet_api_key" class="form-label">API Key</label>
                            <input type="password" id="wapnet_api_key" name="wapnet_api_key"
                                   value="<?= e($wapnetApiKey) ?>" maxlength="255"
                                   class="form-control" placeholder="Token rahsia dari wap.net"
                                   autocomplete="off">
                            <p class="form-help">Tidak akan dipaparkan semula selepas disimpan — simpan nota sendiri.</p>
                        </div>
                        <div>
                            <label for="wapnet_default_template" class="form-label">Template lalai</label>
                            <input type="text" id="wapnet_default_template" name="wapnet_default_template"
                                   value="<?= e($wapnetTemplate) ?>" maxlength="100"
                                   class="form-control" placeholder="general_notification">
                        </div>
                    </div>
                </section>

                <!-- TAB: Operations / contact ------------------------------------ -->
                <section class="settings-pane" data-pane="operations" aria-labelledby="tab-operations" hidden>
                    <h2 class="card-heading" id="tab-operations">Operasi &amp; Hubungan</h2>
                    <p class="muted">Maklumat hubungan yang digunakan dalam emel blast, footer sistem dan halaman sokongan.</p>

                    <div class="form-group form-grid form-grid-2">
                        <div>
                            <label for="system_contact_phone" class="form-label">Telefon Helpdesk</label>
                            <input type="text" id="system_contact_phone" name="system_contact_phone"
                                   value="<?= e($contactPhone) ?>" maxlength="30"
                                   class="form-control" placeholder="03-XXXXXXX">
                        </div>
                        <div>
                            <label for="system_contact_email" class="form-label">Emel Helpdesk</label>
                            <input type="email" id="system_contact_email" name="system_contact_email"
                                   value="<?= e($contactEmail) ?>" maxlength="150"
                                   class="form-control" placeholder="helpdesk@contoh.my">
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Semua Tetapan</button>
                    <a href="<?= url('/admin') ?>" class="btn btn-ghost">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>

        <aside class="card settings-preview">
            <div class="card-body">
                <h2 class="card-heading">Pratonton</h2>
                <p class="muted">Paparan bagaimana identiti anda akan kelihatan.</p>
                <div class="brand-preview">
                    <div class="brand-preview-mark">
                        <?php if ($logoPath): ?>
                            <img src="<?= url('/brand/logo') ?>" alt="">
                        <?php else: ?>
                            <span aria-hidden="true">MK</span>
                        <?php endif; ?>
                    </div>
                    <div class="brand-preview-text">
                        <strong><?= e($appName) ?></strong>
                        <?php if ($tagline !== ''): ?>
                            <small><?= e($tagline) ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($qrPath): ?>
                    <div class="qr-public" style="margin-top: var(--space-4);">
                        <img src="<?= url('/brand/qr') ?>" alt="QR pembayaran">
                        <div class="qr-public-text">
                            <h4>QR Pembayaran Sistem</h4>
                            <p>Dipaparkan sebagai sandaran untuk pelan tanpa QR khusus.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <hr style="border:0;border-top:1px solid var(--color-border);margin: var(--space-5) 0;">

                <h3 class="card-heading" style="font-size:1rem;">Status Integrasi</h3>
                <ul class="integration-status">
                    <li><span>Email Blast</span><span class="badge badge-<?= $emailBlastEnabled ? 'success' : 'neutral' ?>"><?= $emailBlastEnabled ? 'Aktif' : 'Tidak aktif' ?></span></li>
                    <li><span>wap.net (WhatsApp)</span><span class="badge badge-<?= $wapnetEnabled ? 'success' : 'neutral' ?>"><?= $wapnetEnabled ? 'Aktif' : 'Tidak aktif' ?></span></li>
                    <li><span>Logo</span><span class="badge badge-<?= $logoPath ? 'success' : 'neutral' ?>"><?= $logoPath ? 'Ditetapkan' : 'Tiada' ?></span></li>
                    <li><span>QR Sistem</span><span class="badge badge-<?= $qrPath ? 'success' : 'neutral' ?>"><?= $qrPath ? 'Ditetapkan' : 'Tiada' ?></span></li>
                </ul>
            </div>
        </aside>
    </div>
</form>

<!-- Email blast composer / history (separate form) -->
<section class="card blast-composer" aria-labelledby="blast-heading">
    <div class="card-body">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Komunikasi</span>
                <h2 id="blast-heading" class="card-title">Hantar Email Blast</h2>
                <p class="muted">Hantar emel sekarang kepada pengguna sasaran. Log penuh dipaparkan di bawah.</p>
            </div>
        </div>

        <?php if (!$emailBlastEnabled): ?>
            <div class="alert alert-info" role="status">
                Email blast belum diaktifkan. Aktifkan <strong>Email Blast</strong> dalam tab di atas, simpan, kemudian kembali untuk menghantar.
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/admin/settings/blast') ?>" class="form-grid" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="blast_subject" class="form-label">Subjek</label>
                <input type="text" id="blast_subject" name="subject" required maxlength="200"
                       value="<?= e($emailBlastDefaultSubject) ?>" class="form-control">
            </div>
            <div class="form-group">
                <label for="blast_message" class="form-label">Mesej</label>
                <textarea id="blast_message" name="message" required rows="6" maxlength="4000"
                          class="form-control"
                          placeholder="Tulis mesej anda di sini..."></textarea>
            </div>
            <div class="form-group form-grid form-grid-2">
                <div>
                    <label for="blast_target" class="form-label">Sasaran</label>
                    <select id="blast_target" name="target_role" class="form-control">
                        <option value="all">Semua pengguna</option>
                        <option value="member">Hanya ahli</option>
                        <option value="admin">Pentadbir sahaja</option>
                        <option value="super_admin">Super Admin sahaja</option>
                        <option value="staff">Staf sahaja</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary" <?= $emailBlastEnabled ? '' : 'disabled' ?>>
                        Hantar Blast
                    </button>
                </div>
            </div>
        </form>

        <?php if (($blastCount ?? 0) > 0): ?>
            <h3 class="card-heading" style="font-size:1rem;margin-top:var(--space-5);">Log Blast Terkini</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tarikh</th>
                            <th>Subjek</th>
                            <th>Sasaran</th>
                            <th>Penerima</th>
                            <th>Status</th>
                            <th>Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blasts as $b): ?>
                            <tr>
                                <td><?= e($b['sent_at'] ?: $b['created_at']) ?></td>
                                <td><?= e(mb_strimwidth($b['subject'], 0, 60, '…')) ?></td>
                                <td><span class="badge badge-neutral"><?= e($b['target_role']) ?></span></td>
                                <td><?= e((string) $b['recipient_count']) ?></td>
                                <td><span class="badge badge-<?= $statusTone($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                                <td><?= e($b['creator_name'] ?? ('#' . $b['created_by'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted" style="margin-top:var(--space-4);">Belum ada blast dihantar.</p>
        <?php endif; ?>
    </div>
</section>
