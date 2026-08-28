<?php
/**
 * @var array $stats
 * @var array $todayDue
 * @var array $comingWeek
 * @var string $todayLabel
 */
$s = $stats ?? [];
$todayDue   = $todayDue ?? [];
$comingWeek = $comingWeek ?? [];
$todayLabel = $todayLabel ?? date('d M Y');

$statCards = [
    ['label' => 'Pelan aktif', 'value' => $s['active_plans'] ?? 0, 'tone' => 'brand', 'icon' => '□'],
    ['label' => 'Ahli aktif', 'value' => $s['total_members'] ?? 0, 'tone' => 'cyan', 'icon' => '◎'],
    ['label' => 'Menunggu semakan', 'value' => $s['pending_verification'] ?? 0, 'tone' => 'amber', 'icon' => '✓'],
    ['label' => 'Caruman tertunggak', 'value' => $s['overdue_count'] ?? 0, 'tone' => 'rose', 'icon' => '!'],
];
$actionCards = [
    ['label' => 'Sahkan pembayaran', 'description' => 'Semak slip dan sahkan caruman ahli.', 'url' => '/admin/payments', 'count' => $s['pending_verification'] ?? 0, 'tone' => 'amber'],
    ['label' => 'Urus pengeluaran', 'description' => 'Tindakan diperlukan untuk permintaan ahli.', 'url' => '/admin/withdrawals', 'count' => $s['pending_withdrawals'] ?? 0, 'tone' => 'brand'],
    ['label' => 'Selesaikan kekurangan', 'description' => 'Pantau kutipan yang belum mencukupi.', 'url' => '/admin/shortfalls', 'count' => $s['shortfall_count'] ?? 0, 'tone' => 'rose'],
];

$sumToday    = '0.00';
foreach ($todayDue as $r) { $sumToday = bcadd($sumToday, (string) $r['expected_amount'], 2); }
$sumWeek     = '0.00';
foreach ($comingWeek as $r) { $sumWeek = bcadd($sumWeek, (string) $r['expected_amount'], 2); }
?>
<?= flash_messages() ?>

<div class="dashboard-page">
    <div class="dashboard-welcome">
        <div>
            <span class="page-eyebrow">Pentadbiran</span>
            <h1>Dashboard</h1>
            <p>Semak keadaan operasi, tindakan tertunda, dan ringkasan kewangan sistem.</p>
        </div>
        <div class="dashboard-welcome-actions">
            <a href="<?= url('/admin/plans/create') ?>" class="btn btn-primary">Cipta Pelan</a>
            <a href="<?= url('/admin/reports/financial') ?>" class="btn btn-secondary">Laporan Kewangan</a>
        </div>
    </div>

    <section aria-label="Ringkasan operasi" class="dashboard-stats grid grid-4">
        <?php foreach ($statCards as $card): ?>
            <article class="stat stat-<?= e($card['tone']) ?>">
                <div class="stat-topline">
                    <span class="stat-icon" aria-hidden="true"><?= e($card['icon']) ?></span>
                    <span class="stat-label"><?= e($card['label']) ?></span>
                </div>
                <strong class="stat-value"><?= e((string) $card['value']) ?></strong>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- Giliran dapat kutu / payout calendar ------------------------------------ -->
    <section class="card payout-calendar" aria-labelledby="payout-heading">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Kalendar Payout</span>
                <h2 id="payout-heading" class="card-title">Giliran dapat kutu hari ini</h2>
                <p class="muted">Senarai penerima payout yang dijadualkan untuk tarikh <?= e($todayLabel) ?> dan 7 hari akan datang.</p>
            </div>
            <a href="<?= url('/admin/payouts') ?>" class="text-link">Lihat semua payout</a>
        </div>

        <?php if ($todayDue === []): ?>
            <div class="empty-state-card">
                <strong>Tiada payout dijadualkan hari ini.</strong>
                <p class="muted">Tarikh payout seterusnya: <?= $comingWeek[0]['payout_date'] ?? '—' ?></p>
            </div>
        <?php else: ?>
            <div class="payout-today-grid">
                <div class="payout-today-summary">
                    <span class="payout-today-label">Jumlah payout hari ini</span>
                    <strong class="payout-today-amount"><?= format_money($sumToday) ?></strong>
                    <span class="badge badge-info"><?= count($todayDue) ?> penerima</span>
                </div>
                <ul class="payout-today-list">
                    <?php foreach ($todayDue as $row): ?>
                        <li class="payout-today-item">
                            <span class="payout-avatar" aria-hidden="true">
                                <?= e(strtoupper(mb_substr((string) ($row['recipient_name'] ?? 'M'), 0, 1))) ?>
                            </span>
                            <span class="payout-info">
                                <strong><?= e($row['recipient_name']) ?></strong>
                                <small><?= e($row['plan_name']) ?> · <?= e($row['member_code']) ?></small>
                            </span>
                            <span class="payout-amount"><?= format_money($row['expected_amount']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($comingWeek !== []): ?>
            <h3 class="card-heading" style="font-size:1rem;margin-top:var(--space-5);">Akan datang (7 hari)</h3>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tarikh</th>
                            <th>Penerima</th>
                            <th>Pelan</th>
                            <th>Status</th>
                            <th style="text-align:right;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comingWeek as $row): ?>
                            <tr>
                                <td><?= e(date('d M', strtotime((string) $row['payout_date']))) ?></td>
                                <td>
                                    <strong><?= e($row['recipient_name']) ?></strong>
                                    <small class="muted d-block"><?= e($row['member_code']) ?></small>
                                </td>
                                <td>
                                    <span><?= e($row['plan_name']) ?></span>
                                    <small class="muted d-block"><?= e($row['plan_code']) ?></small>
                                </td>
                                <td><span class="badge badge-neutral"><?= e(ucfirst($row['status'])) ?></span></td>
                                <td style="text-align:right;"><?= format_money($row['expected_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align:right;"><strong>Jumlah 7 hari</strong></td>
                            <td style="text-align:right;"><strong><?= format_money($sumWeek) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <div class="dashboard-main-grid">
        <section class="card dashboard-actions" aria-labelledby="tindakan-heading">
            <div class="card-heading">
                <div>
                    <span class="section-kicker">Keutamaan</span>
                    <h2 id="tindakan-heading" class="card-title">Tindakan memerlukan perhatian</h2>
                </div>
            </div>
            <div class="action-list">
                <?php foreach ($actionCards as $action): ?>
                    <a class="action-item" href="<?= url($action['url']) ?>">
                        <span class="action-indicator action-indicator-<?= e($action['tone']) ?>" aria-hidden="true"></span>
                        <span class="action-copy">
                            <strong><?= e($action['label']) ?></strong>
                            <small><?= e($action['description']) ?></small>
                        </span>
                        <span class="action-count"><?= e((string) $action['count']) ?></span>
                        <span class="action-arrow" aria-hidden="true">›</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card financial-overview" aria-labelledby="kewangan-heading">
            <div class="card-heading">
                <div>
                    <span class="section-kicker">Kewangan</span>
                    <h2 id="kewangan-heading" class="card-title">Ringkasan aliran dana</h2>
                </div>
                <a href="<?= url('/admin/reports/financial') ?>" class="text-link">Lihat laporan</a>
            </div>
            <dl class="financial-list">
                <div><dt>Jumlah kutipan</dt><dd class="amount-positive"><?= format_money($s['total_collection'] ?? 0) ?></dd></div>
                <div><dt>Jumlah payout</dt><dd><?= format_money($s['total_payout'] ?? 0) ?></dd></div>
                <div><dt>Fi pentadbiran</dt><dd><?= format_money($s['admin_fee_sum'] ?? 0) ?></dd></div>
                <div><dt>Kekurangan</dt><dd class="amount-warning"><?= format_money($s['shortfall_sum'] ?? 0) ?></dd></div>
            </dl>
        </section>
    </div>

    <section class="dashboard-quick-links" aria-labelledby="modul-heading">
        <div class="section-header-inline">
            <div>
                <span class="section-kicker">Modul</span>
                <h2 id="modul-heading">Akses pantas</h2>
            </div>
        </div>
        <div class="quick-link-grid">
            <a href="<?= url('/admin/plans') ?>"><strong>Pelan</strong><span>Cipta dan urus pelan simpanan</span></a>
            <a href="<?= url('/admin/members') ?>"><strong>Ahli</strong><span>Profil, status dan skor ahli</span></a>
            <a href="<?= url('/admin/payouts') ?>"><strong>Payout</strong><span>Jadual dan rekod pembayaran</span></a>
            <a href="<?= url('/admin/credit-scores') ?>"><strong>Skor kredit</strong><span>Pantau risiko dan sejarah skor</span></a>
        </div>
    </section>
</div>
