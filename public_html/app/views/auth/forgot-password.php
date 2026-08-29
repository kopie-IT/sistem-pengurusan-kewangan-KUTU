<div class="auth-shell">
    <aside class="auth-aside" aria-label="Branding">
        <a href="<?= url('/') ?>" class="brand">
            <?= partial('brand', ['logoUrl' => brand_logo_url(), 'fallbackInitials' => brand_initials()]) ?>
            <span><?= e(brand_name()) ?></span>
        </a>

        <div class="content">
            <h2>Lupa kata laluan?</h2>
            <p>
                Masukkan emel yang berdaftar dengan akaun anda. Kami akan
                hantar pautan untuk menetapkan kata laluan baru.
            </p>
            <ul>
                <li>Pautan sah selama 1 jam</li>
                <li>Sekali guna sahaja</li>
                <li>Sila semak folder spam jika tiada dalam peti masuk</li>
            </ul>
        </div>

        <p class="meta">&copy; <?= date('Y') ?> <?= e(brand_name()) ?></p>
    </aside>

    <section class="auth-main">
        <div class="auth-card">
            <a href="<?= url('/') ?>" class="auth-brand-mobile">
                <?= partial('brand', ['logoUrl' => brand_logo_url(), 'fallbackInitials' => brand_initials()]) ?>
                <span><?= e(brand_name()) ?></span>
            </a>

            <h1>Reset kata laluan</h1>
            <p class="subtitle">Masukkan emel akaun anda untuk menerima pautan tetapan semula.</p>

            <?= flash_messages() ?>

            <form method="POST" action="<?= url('/forgot-password') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email" class="form-label">Emel</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           placeholder="nama@contoh.com"
                           value="<?= e($_SESSION['old']['email'] ?? '') ?>"
                           class="form-control">
                    <?php unset($_SESSION['old']); ?>
                </div>

                <?= captcha_field('forgot_password') ?>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Hantar Pautan Reset
                </button>

                <p class="text-center text-muted" style="font-size: 0.9rem; margin-top: 1.25rem;">
                    Ingat kata laluan anda?
                    <a href="<?= url('/login') ?>">Log masuk</a>
                </p>
            </form>
        </div>
    </section>
</div>
