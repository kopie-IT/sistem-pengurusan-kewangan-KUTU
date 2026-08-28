<?php
/** @var array $ledger */
/** @var string|null $typeFilter */
/** @var string|null $dateFilter */
$typeBadge = function (?string $t): string {
    return match ($t ?? '') {
        'contribution', 'payment', 'payout' => 'success',
        'admin_fee' => 'info',
        'shortfall' => 'danger',
        'withdrawal' => 'warning',
        default => 'neutral',
    };
};
?>
<?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Laporan Kewangan</h1>
                <p class="muted">Ledger transaksi kewangan.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/reports/export') ?>" class="btn btn-secondary">Eksport CSV</a>
            </div>
        </div>

        <form method="GET" action="<?= url('/admin/reports/financial') ?>" class="toolbar">
            <select name="type" class="form-control" style="max-width: 200px;">
                <option value="">Semua jenis</option>
                <?php foreach (['contribution', 'payment', 'payout', 'admin_fee', 'shortfall', 'withdrawal'] as $t): ?>
                    <option value="<?= e($t) ?>" <?= ($typeFilter ?? '') === $t ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $t))) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="date" class="form-control" style="max-width: 180px;" value="<?= e($dateFilter ?? '') ?>">
            <button type="submit" class="btn btn-secondary">Tapis</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Ahli</th>
                        <th>Pelan</th>
                        <th>Amaun</th>
                        <th>Mata Wang</th>
                        <th>Keterangan</th>
                        <th>Tarikh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ledger)): ?>
                        <tr><td colspan="7" class="empty-state">Tiada rekod transaksi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ledger as $l): ?>
                            <tr>
                                <td><span class="badge badge-<?= $typeBadge($l->transaction_type ?? null) ?>"><?= e(ucfirst(str_replace('_', ' ', $l->transaction_type ?? '-'))) ?></span></td>
                                <td><?= e($l->member_id ?? '-') ?></td>
                                <td><?= e($l->plan_id ?? '-') ?></td>
                                <td><?= format_money($l->amount ?? 0) ?></td>
                                <td><?= e($l->currency ?? 'RM') ?></td>
                                <td><?= e($l->description ?? '-') ?></td>
                                <td><?= e($l->created_at ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
