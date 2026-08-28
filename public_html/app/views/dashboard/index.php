<?php
/** @var \App\Models\User|null $user */
$isAdmin = $user?->isAdmin() ?? false;
?>
<?= flash_messages() ?>

<div class="dashboard-page member-dashboard">
    <div class="dashboard-welcome">
        <div>
            <span class="page-eyebrow"><?= $isAdmin ? 'Pentadbiran' : 'Akaun Ahli' ?></span>
            <h1>Selamat datang, <?= e($user?->name ?? 'Pengguna') ?></h1>
            <p>Urus pelan, caruman, payout, dan makluman anda dari satu tempat.</p>
        </div>
        <div class="dashboard-welcome-actions">
            <?php if ($isAdmin): ?>
                <a href="<?= url('/admin') ?>" class="btn btn-primary">Buka Dashboard Pentadbir</a>
            <?php else: ?>
                <a href="<?= url('/plans') ?>" class="btn btn-primary">Lihat Pelan</a>
                <a href="<?= url('/payments') ?>" class="btn btn-secondary">Bayaran Saya</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="member-dashboard-grid">
        <section class="card account-summary" aria-labelledby="akaun-heading">
            <div class="card-heading">
                <div>
                    <span class="section-kicker">Akaun</span>
                    <h2 id="akaun-heading" class="card-title">Ringkasan profil</h2>
                </div>
                <span class="badge badge-success">Aktif</span>
            </div>
            <dl class="account-details">
                <div><dt>Nama</dt><dd><?= e($user?->name ?? '-') ?></dd></div>
                <div><dt>Emel</dt><dd><?= e($user?->email ?? '-') ?></dd></div>
                <div><dt>Peranan</dt><dd><?= e(ucfirst($user?->roleSlug ?? 'member')) ?></dd></div>
            </dl>
            <a href="<?= url('/profile') ?>" class="btn btn-secondary btn-sm">Kemaskini Profil</a>
        </section>

        <section class="card" aria-labelledby="seterusnya-heading">
            <div class="card-heading">
                <div>
                    <span class="section-kicker">Seterusnya</span>
                    <h2 id="seterusnya-heading" class="card-title">Tindakan cepat</h2>
                </div>
            </div>
            <div class="action-list">
                <a class="action-item" href="<?= url('/payments') ?>">
                    <span class="action-indicator action-indicator-amber" aria-hidden="true"></span>
                    <span class="action-copy"><strong>Semak caruman</strong><small>Lihat baki dan tarikh pembayaran.</small></span>
                    <span class="action-arrow" aria-hidden="true">›</span>
                </a>
                <a class="action-item" href="<?= url('/payouts/me') ?>">
                    <span class="action-indicator action-indicator-cyan" aria-hidden="true"></span>
                    <span class="action-copy"><strong>Semak payout</strong><small>Rujuk jadual pembayaran anda.</small></span>
                    <span class="action-arrow" aria-hidden="true">›</span>
                </a>
                <a class="action-item" href="<?= url('/credit-score') ?>">
                    <span class="action-indicator action-indicator-brand" aria-hidden="true"></span>
                    <span class="action-copy"><strong>Skor kredit</strong><small>Pantau tahap kelayakan anda.</small></span>
                    <span class="action-arrow" aria-hidden="true">›</span>
                </a>
            </div>
        </section>
    </div>

    <section class="dashboard-quick-links" aria-labelledby="akaun-modul-heading">
        <div class="section-header-inline">
            <div>
                <span class="section-kicker">Navigasi</span>
                <h2 id="akaun-modul-heading">Urus akaun anda</h2>
            </div>
        </div>
        <div class="quick-link-grid">
            <a href="<?= url('/plans') ?>"><strong>Pelan tersedia</strong><span>Sertai dan semak pelan kutu</span></a>
            <a href="<?= url('/calendar/contribution') ?>"><strong>Kalendar caruman</strong><span>Tarikh caruman yang penting</span></a>
            <a href="<?= url('/withdrawals') ?>"><strong>Pengeluaran</strong><span>Status permintaan pengeluaran</span></a>
            <a href="<?= url('/notifications') ?>"><strong>Makluman</strong><span>Notis sistem terkini</span></a>
        </div>
    </section>
</div>
