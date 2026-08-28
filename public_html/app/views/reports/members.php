<?php
/** @var array $members */
$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'active'   => 'success',
        'pending'  => 'warning',
        'suspended'=> 'danger',
        default => 'neutral',
    };
};
?>
<?= flash_messages() ?>

<div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Laporan Ahli</h1>
                <p class="muted">Skor kredit dan ringkasan bayaran ahli.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/reports/export') ?>" class="btn btn-secondary">Eksport CSV</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Nama</th>
                        <th>Skor</th>
                        <th>Bayaran Lewat</th>
                        <th>Jumlah Dibayar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr><td colspan="6" class="empty-state">Tiada ahli direkodkan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td><?= e($m->member_code ?? '-') ?></td>
                                <td><?= e($m->name ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($m->credit_score)): ?>
                                        <span class="badge badge-<?= ($m->credit_score ?? 0) < 40 ? 'danger' : (($m->credit_score ?? 0) < 70 ? 'warning' : 'success') ?>"><?= e((string) $m->credit_score) ?></span>
                                    <?php else: ?>
                                        <span class="muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e((string) ($m->late_payments ?? $m->latePayments ?? 0)) ?></td>
                                <td><?= format_money($m->total_paid ?? $m->totalPaid ?? 0) ?></td>
                                <td><span class="badge badge-<?= $statusBadge($m->status ?? null) ?>"><?= e(ucfirst($m->status ?? 'tiada')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
