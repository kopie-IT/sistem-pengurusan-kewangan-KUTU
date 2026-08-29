<?php
/** @var array $summary */
/** @var array $byType */
/** @var array $ledger */
/** @var array $plans */
/** @var string|null $typeFilter */
/** @var string|null $dateFilter */
/** @var int $planFilter */

$typeBadge = function (?string $t): string {
    return match ($t ?? '') {
        'contribution', 'payment', 'payout' => 'success',
        'admin_fee' => 'info',
        'shortfall' => 'danger',
        'withdrawal' => 'warning',
        default => 'neutral',
    };
};

$totalIn      = (float) ($summary['total_in'] ?? 0);
$totalOut     = (float) ($summary['total_out'] ?? 0);
$totalFee     = (float) ($summary['total_fee'] ?? 0);
$totalShort   = (float) ($summary['total_shortfall'] ?? 0);
$totalCount   = (int) ($summary['total_count'] ?? 0);
$netBalance   = $totalIn - $totalOut;
?>
<?= flash_messages() ?>

<div class="page-header">
    <div>
        <span class="page-eyebrow">Pentadbiran</span>
        <h1>Laporan Kewangan</h1>
        <p class="muted">Ringkasan & transaksi kewangan dari <code>ledger_transactions</code>.</p>
    </div>
    <div class="actions">
        <a href="<?= url('/admin/reports/export?type=financial') ?>" class="btn btn-secondary">Eksport CSV</a>
    </div>
</div>

<div class="grid grid-4" style="margin-bottom: 1.5rem;">
    <div class="card" style="padding: 1.25rem; border-left: 4px solid #16a34a;">
        <div class="muted small" style="text-transform: uppercase; letter-spacing: 0.5px;">Jumlah Masuk</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #16a34a; margin-top: 0.25rem;">
            <?= format_money($totalIn) ?>
        </div>
        <div class="muted small" style="margin-top: 0.25rem;">Caruman & Bayaran</div>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid #2563eb;">
        <div class="muted small" style="text-transform: uppercase; letter-spacing: 0.5px;">Baki Bersih</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #2563eb; margin-top: 0.25rem;">
            <?= format_money($netBalance) ?>
        </div>
        <div class="muted small" style="margin-top: 0.25rem;">Selepas tolak payout</div>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid #f59e0b;">
        <div class="muted small" style="text-transform: uppercase; letter-spacing: 0.5px;">Yuran Pentadbir</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #f59e0b; margin-top: 0.25rem;">
            <?= format_money($totalFee) ?>
        </div>
        <div class="muted small" style="margin-top: 0.25rem;">Jumlah yuran admin</div>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid #dc2626;">
        <div class="muted small" style="text-transform: uppercase; letter-spacing: 0.5px;">Kekurangan</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626; margin-top: 0.25rem;">
            <?= format_money($totalShort) ?>
        </div>
        <div class="muted small" style="margin-top: 0.25rem;">Jumlah shortfall</div>
    </div>
</div>

<div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <h3 style="margin: 0 0 0.75rem 0; font-size: 1rem;">Ringkasan Mengikut Jenis</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Jenis</th>
                    <th style="text-align:right;">Bilangan</th>
                    <th style="text-align:right;">Jumlah</th>
                    <th style="text-align:right;">%</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalAll = array_sum(array_map(fn ($r) => (float) $r['total'], $byType));
                foreach ($byType as $r):
                    $pct = $totalAll > 0 ? ((float) $r['total'] / $totalAll) * 100 : 0;
                ?>
                    <tr>
                        <td><span class="badge badge-<?= $typeBadge($r['transaction_type']) ?>"><?= e(ucfirst(str_replace('_', ' ', $r['transaction_type']))) ?></span></td>
                        <td style="text-align:right;"><?= (int) $r['cnt'] ?></td>
                        <td style="text-align:right;"><strong><?= format_money($r['total']) ?></strong></td>
                        <td style="text-align:right;"><?= number_format($pct, 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<form method="GET" action="<?= url('/admin/reports/financial') ?>" class="toolbar">
    <select name="type" class="form-control" style="max-width: 200px;">
        <option value="">Semua jenis</option>
        <?php foreach (['contribution', 'payment', 'payout', 'admin_fee', 'shortfall', 'withdrawal'] as $t): ?>
            <option value="<?= e($t) ?>" <?= ($typeFilter ?? '') === $t ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $t))) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="plan" class="form-control" style="max-width: 220px;">
        <option value="0">Semua pelan</option>
        <?php foreach ($plans as $pl): ?>
            <option value="<?= (int) $pl->id ?>" <?= $planFilter === (int) $pl->id ? 'selected' : '' ?>><?= e($pl->name) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date" class="form-control" style="max-width: 180px;" value="<?= e($dateFilter ?? '') ?>">
    <button type="submit" class="btn btn-secondary">Tapis</button>
    <a href="<?= url('/admin/reports/financial') ?>" class="btn btn-ghost">Reset</a>
</form>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Jenis</th>
                <th>Ahli</th>
                <th>Pelan</th>
                <th style="text-align:right;">Amaun</th>
                <th>Keterangan</th>
                <th>Tarikh</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ledger)): ?>
                <tr><td colspan="6" class="empty-state">Tiada rekod transaksi.</td></tr>
            <?php else: ?>
                <?php foreach ($ledger as $l): ?>
                    <tr>
                        <td><span class="badge badge-<?= $typeBadge($l->transaction_type ?? null) ?>"><?= e(ucfirst(str_replace('_', ' ', $l->transaction_type ?? '-'))) ?></span></td>
                        <td>
                            <?php if (!empty($l->member_name)): ?>
                                <div style="font-weight: 600;"><?= e($l->member_name) ?></div>
                                <?php if (!empty($l->member_code)): ?>
                                    <div class="muted small"><?= e($l->member_code) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="muted small">#<?= (int) ($l->member_id ?? 0) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($l->plan_name)): ?>
                                <div style="font-weight: 600;"><?= e($l->plan_name) ?></div>
                                <?php if (!empty($l->plan_code)): ?>
                                    <div class="muted small"><?= e($l->plan_code) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;"><strong><?= format_money($l->amount ?? 0) ?></strong></td>
                        <td style="max-width: 320px;"><?= e($l->description ?? '-') ?></td>
                        <td>
                            <div><?= e(date('d/m/Y', strtotime((string) $l->created_at))) ?></div>
                            <div class="muted small"><?= e(date('H:i', strtotime((string) $l->created_at))) ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
