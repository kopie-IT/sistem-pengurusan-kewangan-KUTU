<div class="auth-shell">
    <aside class="auth-aside" aria-label="Branding">
        <a href="<?= url('/') ?>" class="brand">
            <span class="nav-brand-mark" aria-hidden="true">MK</span>
            <span>Sistem Main Kutu</span>
        </a>

        <div class="content">
            <h2>Mulakan perjalanan Main Kutu anda.</h2>
            <p>Daftar akaun percuma untuk akses kepada semua Plan &mdash; jadual caruman, payout, dan Credit Score.</p>
            <ul>
                <li>Pendaftaran percuma</li>
                <li>Tiada kad kredit diperlukan</li>
                <li>Credit Score 100 untuk akaun baru</li>
                <li>Audit trail &amp; keselamatan data</li>
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

            <h1>Daftar akaun</h1>
            <p class="subtitle">Cipta akaun baru untuk mula menggunakan sistem.</p>

            <?= flash_messages() ?>

            <form method="POST" action="<?= url('/register') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="name" class="form-label">Nama Penuh</label>
                    <input type="text" id="name" name="name" required autocomplete="name"
                           placeholder="Nama penuh anda"
                           class="form-control">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Emel</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           placeholder="nama@contoh.com"
                           class="form-control">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Laluan</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                           placeholder="Minimum 8 aksara"
                           minlength="8"
                           class="form-control">
                    <p class="form-help">Minimum 8 aksera, gabungan huruf dan nombor.</p>
                </div>

                <div class="form-group">
                    <label for="password_confirm" class="form-label">Sahkan Kata Laluan</label>
                    <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"
                           placeholder="Taip semula kata laluan"
                           minlength="8"
                           class="form-control">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Daftar Akaun</button>

                <p class="text-center text-muted" style="font-size: 0.9rem; margin-top: 1.25rem;">
                    Sudah ada akaun?
                    <a href="<?= url('/login') ?>">Log masuk</a>
                </p>
            </form>
        </div>
    </section>
</div>
