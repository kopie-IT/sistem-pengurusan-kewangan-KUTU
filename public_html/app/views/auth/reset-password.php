<div class="auth-shell">
    <aside class="auth-aside" aria-label="Branding">
        <a href="<?= url('/') ?>" class="brand">
            <span class="nav-brand-mark" aria-hidden="true">MK</span>
            <span>Sistem Main Kutu</span>
        </a>

        <div class="content">
            <h2>Tetapkan kata laluan baru.</h2>
            <p>
                <?php if (!empty($hasToken)): ?>
                    Anda menerima pautan ini kerana pentadbir telah menetapkan semula kata laluan anda.
                    Sila pilih kata laluan baru yang kuat untuk teruskan.
                <?php else: ?>
                    Selamat datang! Untuk keselamatan akaun anda, sila tetapkan kata laluan baru
                    sebelum menggunakan sistem.
                <?php endif; ?>
            </p>
            <ul>
                <li>Minimum 8 aksara</li>
                <li>Sekurang-kurangnya satu huruf besar</li>
                <li>Sekurang-kurangnya satu huruf kecil</li>
                <li>Sekurang-kurangnya satu nombor</li>
            </ul>
        </div>

        <p class="meta">&copy; <?= date('Y') ?> Sistem Pengurusan Main Kutu</p>
    </aside>

    <section class="auth-main">
        <div class="auth-card">
            <a href="<?= url('/') ?>" class="auth-brand-mobile">
                <span class="nav-brand-mark" aria-hidden="true">MK</span>
                <span>Main Kutu</span>
            </a>

            <h1><?= !empty($hasToken) ? 'Reset kata laluan' : 'Tetapkan kata laluan' ?></h1>
            <p class="subtitle">Pilih kata laluan yang kuat dan unik.</p>

            <?= flash_messages() ?>

            <form method="POST" action="<?= url('/reset-password') ?>" novalidate>
                <?= csrf_field() ?>
                <?php if (!empty($hasToken)): ?>
                    <input type="hidden" name="reset_token" value="<?= e($_GET['token'] ?? '') ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Laluan Baru</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                           placeholder="Minimum 8 aksara"
                           minlength="8"
                           class="form-control">
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">Sahkan Kata Laluan</label>
                    <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"
                           placeholder="Taip semula kata laluan"
                           minlength="8"
                           class="form-control">
                    <p class="form-help">Minimum 8 aksara, mengandungi huruf besar, huruf kecil, dan nombor.</p>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <?= !empty($hasToken) ? 'Reset Kata Laluan' : 'Tetapkan &amp; Teruskan' ?>
                </button>

                <?php if (empty($hasToken)): ?>
                    <p class="text-center text-muted" style="font-size: 0.9rem; margin-top: 1.25rem;">
                        <a href="<?= url('/logout') ?>">Log keluar</a>
                    </p>
                <?php endif; ?>
            </form>
        </div>
    </section>
</div>
