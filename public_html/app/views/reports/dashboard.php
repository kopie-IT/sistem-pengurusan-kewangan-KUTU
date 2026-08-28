<?php
/** @var array $stats */
/** @var array $recentLedger */
/** @var array $shortfalls */
$s = $stats ?? [];
$summaryCards = [
    ['label' => 'Jumlah Kutipan', 'value' => format_money($s['total_collection'] ?? 0), 'badge' => 'success'],
    ['label' => 'Jumlah Payout',  'value' => format_money($s['total_payout'] ?? 0),     'badge' => 'neutral'],
    ['label' => 'Fi Admin',       'value' => format_money($s['admin_fee'] ?? 0),         'badge' => 'info'],
    ['label' => 'Kekurangan',     'value' => format_money($s['shortfall'] ?? 0),         'badge' => 'warning'],
];
?>
<?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Papan Pemuka Laporan</h1>
                <p class="muted">Ringkasan kewangan sistem.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/reports/export') ?>" class="btn btn-secondary">Eksport CSV</a>
            </div>
        </div>

        <div class="grid grid-4">
            <?php foreach ($summaryCards as $c): ?>
                <div class="card card-sm">
                    <span class="badge badge-<?= $c['badge'] ?>"><?= e($c['label']) ?></span>
                    <div style="font-size: 1.5rem; font-weight: 700; margin-top: 0.5rem;"><?= e($c['value']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-3 mt-4">
            <a href="<?= url('/admin/reports/financial') ?>" class="btn btn-secondary btn-block">Laporan Kewangan</a>
            <a href="<?= url('/admin/reports/plans') ?>" class="btn btn-secondary btn-block">Prestasi Pelan</a>
            <a href="<?= url('/admin/reports/members') ?>" class="btn btn-secondary btn-block">Laporan Ahli</a>
        </div>

        <h2 class="card-title mt-5">Ledger Terkini</h2>
        <?php if (empty($recentLedger)): ?>
            <div class="card">
                <div class="empty-state">Tiada rekod ledger.</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Ahli</th>
                            <th>Amaun</th>
                            <th>Tarikh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLedger as $l): ?>
                            <tr>
                                <td><?= e($l->transaction_type ?? $l->type ?? '-') ?></td>
                                <td><?= e($l->member_id ?? '-') ?></td>
                                <td><?= format_money($l->amount ?? 0) ?></td>
                                <td><?= e($l->created_at ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="card-title mt-5">Ringkasan Kekurangan</h2>
        <?php if (empty($shortfalls)): ?>
            <div class="card">
                <div class="empty-state">Tiada kekurangan direkodkan.</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Pelan</th><th>Kekurangan</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shortfalls as $sf): ?>
                            <tr>
                                <td><?= e($sf->plan_name ?? $sf->plan_id ?? '-') ?></td>
                                <td><?= format_money($sf->shortfall_amount ?? 0) ?></td>
                                <td><span class="badge badge-<?= ($sf->status ?? '') === 'resolved' ? 'success' : 'warning' ?>"><?= e(ucfirst($sf->status ?? 'terbuka')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
