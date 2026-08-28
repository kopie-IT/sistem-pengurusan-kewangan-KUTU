<?php
/** @var array $settings */
$appName    = $settings['app_name']    ?? 'Sistem Pengurusan Main Kutu';
$tagline    = $settings['brand_tagline'] ?? '';
$logoPath   = $settings['logo_path']   ?? null;
$qrPath     = $settings['payment_qr_path'] ?? null;
?>
<section class="page-header">
    <div>
        <span class="page-eyebrow">Pentadbiran</span>
        <h1 class="page-title">Tetapan Sistem</h1>
        <p class="page-subtitle">Urus nama sistem, tagline, logo dan QR pembayaran yang dipaparkan di seluruh aplikasi.</p>
    </div>
</section>

<?= flash_messages() ?>

<div class="settings-grid">
    <div class="card">
        <div class="card-body">
            <h2 class="card-heading">Identiti</h2>
            <p class="muted">Maklumat ini dipaparkan di header, footer, dan halaman log masuk.</p>

            <form method="POST" action="<?= url('/admin/settings') ?>" enctype="multipart/form-data" class="form-grid" novalidate>
                <?= csrf_field() ?>

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

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Tetapan</button>
                    <a href="<?= url('/admin') ?>" class="btn btn-ghost">Kembali ke Dashboard</a>
                </div>
            </form>
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
        </div>
    </aside>
</div>
