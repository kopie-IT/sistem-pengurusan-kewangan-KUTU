<?php
/** @var array $stats */
$s = $stats ?? [];
$statCards = [
    ['label' => 'Pelan Aktif',            'value' => $s['active_plans'] ?? 0,          'badge' => 'info'],
    ['label' => 'Jumlah Ahli',            'value' => $s['total_members'] ?? 0,         'badge' => 'purple'],
    ['label' => 'Menunggu Pengesahan',    'value' => $s['pending_verification'] ?? 0,  'badge' => 'warning'],
    ['label' => 'Tertunggak',             'value' => $s['overdue'] ?? 0,              'badge' => 'danger'],
    ['label' => 'Payout Hari Ini',        'value' => $s['today_payout'] ?? 0,         'badge' => 'info'],
    ['label' => 'Payout Akan Datang',     'value' => $s['upcoming_payout'] ?? 0,      'badge' => 'success'],
    ['label' => 'Jumlah Kutipan',         'value' => format_money($s['total_collection'] ?? 0), 'badge' => 'success'],
    ['label' => 'Jumlah Payout',          'value' => format_money($s['total_payout'] ?? 0),     'badge' => 'neutral'],
    ['label' => 'Fi Admin',               'value' => format_money($s['admin_fee'] ?? 0),         'badge' => 'info'],
    ['label' => 'Kekurangan',             'value' => format_money($s['shortfall'] ?? 0),         'badge' => 'warning'],
    ['label' => 'Ahli Skor Rendah',       'value' => $s['low_score_members'] ?? 0,    'badge' => 'danger'],
    ['label' => 'Kekurangan (unit)',      'value' => $s['shortfall_count'] ?? 0,     'badge' => 'warning'],
];
$links = [
    ['label' => 'Pelan',         'url' => '/admin/plans'],
    ['label' => 'Ahli',          'url' => '/admin/members'],
    ['label' => 'Pembayaran',    'url' => '/admin/payments'],
    ['label' => 'Payout',        'url' => '/admin/payouts'],
    ['label' => 'Kekurangan',    'url' => '/admin/shortfalls'],
    ['label' => 'Pengeluaran',   'url' => '/admin/withdrawals'],
    ['label' => 'Laporan',       'url' => '/admin/reports/dashboard'],
    ['label' => 'Skor Kredit',   'url' => '/admin/credit-scores'],
];
?>
<?= flash_messages() ?>

<div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Papan Pemuka Pentadbir</h1>
                <p class="muted">Gambaran keseluruhan sistem Main Kutu.</p>
            </div>
        </div>

        <div class="grid grid-4">
            <?php foreach ($statCards as $c): ?>
                <div class="card card-sm">
                    <span class="badge badge-<?= $c['badge'] ?>"><?= e($c['label']) ?></span>
                    <div style="font-size: 1.75rem; font-weight: 700; margin-top: 0.5rem;"><?= e((string) $c['value']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-2 mt-5">
            <div class="card">
                <h2 class="card-title">Pautan Pantas</h2>
                <div class="grid grid-2" style="gap: 0.75rem; margin-top: 0.75rem;">
                    <?php foreach ($links as $l): ?>
                        <a href="<?= url($l['url']) ?>" class="btn btn-secondary btn-block"><?= e($l['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h2 class="card-title">Arah Tuju</h2>
                <ul class="small muted" style="list-style: none; padding: 0; margin: 0.75rem 0; display: grid; gap: 0.5rem;">
                    <li>Sahkan bayaran tertunggak: <strong><?= e((string) ($s['pending_verification'] ?? 0)) ?></strong></li>
                    <li>Kekurangan perlu diselesaikan: <strong><?= e((string) ($s['shortfall_count'] ?? 0)) ?></strong></li>
                    <li>Pengeluaran menunggu: <strong><?= e((string) ($s['pending_withdrawals'] ?? 0)) ?></strong></li>
                    <li>Ahli skor rendah: <strong><?= e((string) ($s['low_score_members'] ?? 0)) ?></strong></li>
                </ul>
            </div>
        </div>
