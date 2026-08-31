<?php
/** @var \App\Models\User|null $user */
/** @var \App\Models\Member|null $member */
/** @var array{score: int|null, level: string} $score */
/** @var array<int, array<string, mixed>> $unpaidSchedules */
/** @var array{count:int, total:string} $unpaidSummary */
$isAdmin = $user?->isAdmin() ?? false;

$scoreValue = $score['score'] ?? null;
$scoreLevel = $score['level'] ?? 'unknown';

$scoreBadgeClass = match ($scoreLevel) {
    'excellent' => 'badge-success',
    'good'      => 'badge-info',
    'fair'      => 'badge-warning',
    'risk'      => 'badge-warning',
    'high_risk' => 'badge-danger',
    default     => 'badge-neutral',
};
$scoreLabel = match ($scoreLevel) {
    'excellent' => 'Cemerlang',
    'good'      => 'Baik',
    'fair'      => 'Sederhana',
    'risk'      => 'Berisiko',
    'high_risk' => 'Berisiko Tinggi',
    default     => 'Belum Dinilai',
};
$scoreDescription = match ($scoreLevel) {
    'excellent' => 'Rekod pembayaran sangat baik. Teruskan!',
    'good'      => 'Rekod pembayaran stabil.',
    'fair'      => 'Boleh diperbaiki dengan pembayaran tepat masa.',
    'risk'      => 'Terdapat kelewatan berulang. Sila semak jadual.',
    'high_risk' => 'Risiko tinggi. Hubungi pentadbir untuk pelan pemulihan.',
    default     => 'Skor kredit anda belum dikira. Sertai pelan untuk mula.',
};

$scorePercent = $scoreValue !== null ? max(0, min(100, (int) $scoreValue)) : 0;
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

    <!-- ============================================================== -->
    <!-- Credit score card (placed immediately below the welcome block). -->
    <!-- ============================================================== -->
    <section class="card credit-score-card" aria-labelledby="credit-score-heading">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Skor Kredit</span>
                <h2 id="credit-score-heading" class="card-title">Tahap kelayakan semasa anda</h2>
            </div>
            <span class="badge <?= e($scoreBadgeClass) ?>"><?= e($scoreLabel) ?></span>
        </div>

        <div class="credit-score-grid">
            <div class="credit-score-meter" role="img"
                 aria-label="Skor kredit <?= (int) $scorePercent ?> daripada 100">
                <svg class="credit-score-ring" viewBox="0 0 120 120" width="120" height="120" aria-hidden="true">
                    <circle class="credit-score-ring-track" cx="60" cy="60" r="52" />
                    <circle class="credit-score-ring-fill" cx="60" cy="60" r="52"
                            stroke-dasharray="<?= e(number_format(2 * M_PI * 52, 2, '.', '')) ?>"
                            stroke-dashoffset="<?= e(number_format(2 * M_PI * 52 * (1 - $scorePercent / 100), 2, '.', '')) ?>" />
                </svg>
                <div class="credit-score-ring-text">
                    <strong><?= $scoreValue !== null ? (int) $scoreValue : '—' ?></strong>
                    <small>/ 100</small>
                </div>
            </div>

            <div class="credit-score-meta">
                <p class="credit-score-level">Tahap: <strong><?= e($scoreLabel) ?></strong></p>
                <p class="muted"><?= e($scoreDescription) ?></p>
                <ul class="credit-score-factors" aria-label="Faktor yang mempengaruhi skor">
                    <li><span class="factor-dot factor-dot-up" aria-hidden="true"></span>
                        Pembayaran tepat masa &nbsp;<em>+5 setiap caruman</em></li>
                    <li><span class="factor-dot factor-dot-down" aria-hidden="true"></span>
                        Kelewatan / tidak hadir &nbsp;<em>−10 sehingga −25</em></li>
                    <li><span class="factor-dot factor-dot-flat" aria-hidden="true"></span>
                        Penyertaan aktif &amp; payout sempurna &nbsp;<em>+15</em></li>
                </ul>
                <div class="credit-score-actions">
                    <a href="<?= url('/credit-score') ?>" class="btn btn-primary btn-sm">Lihat Sejarah Penuh</a>
                    <?php if ($member !== null): ?>
                        <span class="credit-score-code">Kod Ahli: <strong><?= e($member->memberCode ?? '-') ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================== -->
    <!-- Unpaid / overdue payments (member-facing).                    -->
    <!-- ============================================================== -->
    <?php if ($member !== null): ?>
        <section class="card unpaid-list-card" aria-labelledby="unpaid-heading">
            <div class="card-heading">
                <div>
                    <span class="section-kicker">Caruman</span>
                    <h2 id="unpaid-heading" class="card-title">Pembayaran yang lewat atau belum dibuat</h2>
                    <p class="muted">Senarai caruman anda yang sudah melepasi tarikh akhir. Selesaikan segera untuk mengekalkan skor kredit yang baik.</p>
                </div>
                <div class="unpaid-summary">
                    <span class="badge <?= ((int) ($unpaidSummary['count'] ?? 0)) > 0 ? 'badge-danger' : 'badge-success' ?>">
                        <?= (int) ($unpaidSummary['count'] ?? 0) ?> rekod
                    </span>
                    <span class="unpaid-total">Baki tertunggak: <strong><?= format_money($unpaidSummary['total'] ?? '0.00') ?></strong></span>
                </div>
            </div>

            <?php if (empty($unpaidSchedules)): ?>
                <div class="empty-state-card">
                    <strong>Tiada caruman tertunggak.</strong>
                    <p class="muted">Terima kasih! Semua caruman anda dijelaskan mengikut jadual.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table table-unpaid">
                        <thead>
                            <tr>
                                <th>Pelan</th>
                                <th>Tarikh Akhir</th>
                                <th>Lewat</th>
                                <th>Status</th>
                                <th style="text-align:right;">Baki</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unpaidSchedules as $row):
                                $dueTs   = strtotime((string) ($row['due_date'] ?? 'now'));
                                $todayTs = strtotime(date('Y-m-d'));
                                $daysLate = $dueTs === false || $todayTs < $dueTs
                                    ? 0
                                    : (int) floor(($todayTs - $dueTs) / 86400);
                                $balance = (float) $row['amount'] - (float) $row['amount_paid'];
                                $statusLabel = match ((string) ($row['status'] ?? '')) {
                                    'overdue' => 'Lewat',
                                    'partial' => 'Sebahagian',
                                    'pending' => 'Belum bayar',
                                    default   => ucfirst((string) ($row['status'] ?? '-')),
                                };
                                $statusTone = match ((string) ($row['status'] ?? '')) {
                                    'overdue' => 'badge-danger',
                                    'partial' => 'badge-warning',
                                    default   => 'badge-neutral',
                                };
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= e((string) ($row['plan_name'] ?? '-')) ?></strong>
                                        <small class="muted d-block"><?= e((string) ($row['plan_code'] ?? '')) ?></small>
                                    </td>
                                    <td><?= e(date('d M Y', $dueTs ?: time())) ?></td>
                                    <td>
                                        <?php if ($daysLate > 0): ?>
                                            <span class="badge <?= $daysLate > 30 ? 'badge-danger' : 'badge-warning' ?>"><?= (int) $daysLate ?> hari</span>
                                        <?php else: ?>
                                            <span class="badge badge-neutral">Hari ini</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge <?= $statusTone ?>"><?= e($statusLabel) ?></span></td>
                                    <td style="text-align:right;"><?= format_money(number_format($balance, 2, '.', '')) ?></td>
                                    <td><a href="<?= url('/payments') ?>" class="btn btn-primary btn-sm">Bayar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

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
                <?php if ($member !== null): ?>
                    <div><dt>Skor Kredit</dt><dd>
                        <span class="badge <?= e($scoreBadgeClass) ?>"><?= $scoreValue !== null ? (int) $scoreValue : '—' ?> / 100</span>
                    </dd></div>
                <?php endif; ?>
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
                <a class="action-item" href="<?= url('/plans') ?>">
                    <span class="action-indicator action-indicator-brand" aria-hidden="true"></span>
                    <span class="action-copy"><strong>Pelan tersedia</strong><small>Sertai pelan kutu yang baharu.</small></span>
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
