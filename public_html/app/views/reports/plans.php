<?php
/** @var array $plans */
$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'active' => 'success',
        'open'   => 'info',
        'completed' => 'neutral',
        'suspended' => 'danger',
        'cancelled' => 'neutral',
        default => 'neutral',
    };
};
?>
<?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Prestasi Pelan</h1>
                <p class="muted">Ringkasan prestasi setiap pelan.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/reports/export') ?>" class="btn btn-secondary">Eksport CSV</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pelan</th>
                        <th>Status</th>
                        <th>Bil. Ahli</th>
                        <th>Kutipan</th>
                        <th>Payout</th>
                        <th>Kekurangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plans)): ?>
                        <tr><td colspan="6" class="empty-state">Tiada pelan direkodkan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($plans as $p): ?>
                            <tr>
                                <td><?= e($p->name ?? $p->planCode ?? $p->id) ?></td>
                                <td><span class="badge badge-<?= $statusBadge($p->status ?? null) ?>"><?= e(ucfirst($p->status ?? '-')) ?></span></td>
                                <td><?= e((string) ($p->memberCount ?? $p->member_count ?? '-')) ?></td>
                                <td><?= format_money($p->collection ?? $p->total_collection ?? 0) ?></td>
                                <td><?= format_money($p->payout ?? $p->total_payout ?? 0) ?></td>
                                <td><?= format_money($p->shortfall ?? $p->shortfall_amount ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
