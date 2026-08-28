<div class="auth-shell">
    <aside class="auth-aside" aria-label="Branding">
        <a href="<?= url('/') ?>" class="brand">
            <span class="nav-brand-mark" aria-hidden="true">MK</span>
            <span>Sistem Main Kutu</span>
        </a>

        <div class="content">
            <h2>Urus Plan anda dengan lebih kemas, telus &amp; selamat.</h2>
            <p>Platform digital untuk caruman, bayaran pukal, payout, dan Credit Score ahli &mdash; semuanya di satu tempat.</p>
            <ul>
                <li>Jadual caruman automatik</li>
                <li>Bayaran pukal dengan satu slip</li>
                <li>Credit Score 0&ndash;100 dengan sejarah penuh</li>
                <li>Audit trail &amp; ledger kewangan</li>
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

            <h1>Selamat kembali</h1>
            <p class="subtitle">Log masuk ke akaun anda untuk teruskan.</p>

            <?= flash_messages() ?>

            <form method="POST" action="<?= url('/login') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email" class="form-label">Emel</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           placeholder="nama@contoh.com"
                           class="form-control"
                           value="<?= e(old('email')) ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Laluan</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           placeholder="Masukkan kata laluan"
                           class="form-control">
                    <button type="button" class="input-affix-btn" id="togglePassword"
                            data-password-toggle="password"
                            aria-label="Papar kata laluan" aria-pressed="false"
                            aria-controls="password">
                </div>

                <div class="form-row">
                    <label class="form-check">
                        <input type="checkbox" name="remember" value="1">
                        <span>Ingat saya</span>
                    </label>
                    <a href="#">Lupa kata laluan?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Log Masuk</button>

                <p class="text-center text-muted" style="font-size: 0.9rem; margin-top: 1.25rem;">
                    Belum ada akaun?
                    <a href="<?= url('/register') ?>">Daftar sekarang</a>
                </p>
            </form>
        </div>
    </section>
</div>
