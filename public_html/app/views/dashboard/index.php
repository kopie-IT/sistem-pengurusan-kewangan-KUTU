<section class="hero" style="padding: 3rem 0 2rem;">
    <div class="container">
        <div>
            <span class="hero-eyebrow">
                <span class="badge-dot" aria-hidden="true"></span>
                <?= $user && $user->isAdmin() ? 'Pentadbir' : 'Ahli' ?>
            </span>
            <h1>
                Selamat datang,
                <span class="gradient-text"><?= e($user?->name ?? 'Pengguna') ?></span>
            </h1>
            <p class="lead">
                Anda log masuk sebagai <strong><?= e($user?->email ?? '') ?></strong>.
                Ini adalah paparan ringkas akaun anda. Modul penuh akan ditambah dalam fasa seterusnya.
            </p>
        </div>
    </div>
</section>

<section class="section" style="padding-top: 1.5rem;">
    <div class="container">
        <div class="features-grid">
            <div class="feature">
                <div class="icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <h3>Maklumat Akaun</h3>
                <p><strong>Nama:</strong> <?= e($user?->name ?? '-') ?></p>
                <p><strong>Emel:</strong> <?= e($user?->email ?? '-') ?></p>
                <p><strong>Role:</strong> <?= e(ucfirst($user?->roleSlug ?? '-')) ?></p>
            </div>

            <div class="feature">
                <div class="icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h3>Keselamatan</h3>
                <p>Kata laluan dienkripsi dengan bcrypt. Sesi dilindungi cookie HttpOnly dan SameSite=Strict.</p>
                <p style="margin-top: 0.75rem;">
                    <a href="<?= url('/logout') ?>" class="btn btn-secondary">Log Keluar</a>
                </p>
            </div>

            <div class="feature">
                <div class="icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <h3>Aktiviti Terakhir</h3>
                <p><strong>Log masuk:</strong> <?= e($user?->lastLoginAt ?? 'Tidak diketahui') ?></p>
                <p><strong>IP:</strong> <?= e($user?->lastLoginIp ?? '-') ?></p>
            </div>
        </div>
    </div>
</section>
