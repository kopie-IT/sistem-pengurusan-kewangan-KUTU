<?php
/** @var array $shortfalls */
/** @var string|null $statusFilter */
$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'resolved' => 'success',
        'open' => 'danger',
        'under_review' => 'warning',
        default => 'neutral',
    };
};
?>
<?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Kekurangan Kutipan</h1>
                <p class="muted">Kekurangan antara jangkaan dan kutipan sebenar.</p>
            </div>
        </div>

        <form method="GET" action="<?= url('/admin/shortfalls') ?>" class="toolbar">
            <select name="status" class="form-control" style="max-width: 200px;">
                <option value="">Semua status</option>
                <?php foreach (['open', 'under_review', 'resolved'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($statusFilter ?? '') === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $s))) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Tapis</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pelan</th>
                        <th>Jangkaan</th>
                        <th>Kutipan</th>
                        <th>Kekurangan</th>
                        <th>Tarikh</th>
                        <th>Status</th>
                        <th class="wrap">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($shortfalls)): ?>
                        <tr><td colspan="7" class="empty-state">Tiada rekod kekurangan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($shortfalls as $sf): ?>
                            <tr>
                                <td><?= e($sf->plan_name ?? $sf->plan_id ?? '-') ?></td>
                                <td><?= format_money($sf->expected_amount ?? 0) ?></td>
                                <td><?= format_money($sf->actual_collection ?? 0) ?></td>
                                <td><strong><?= format_money($sf->shortfall_amount ?? 0) ?></strong></td>
                                <td><?= e($sf->created_at ?? '-') ?></td>
                                <td><span class="badge badge-<?= $statusBadge($sf->status ?? null) ?>"><?= e(ucfirst(str_replace('_', ' ', $sf->status ?? 'terbuka'))) ?></span></td>
                                <td class="wrap">
                                    <?php if (($sf->status ?? '') === 'open' || ($sf->status ?? '') === 'under_review'): ?>
                                        <form method="POST" action="<?= url('/admin/shortfalls/' . $sf->id . '/resolve') ?>" style="display:inline-block;">
                                            <?= csrf_field() ?>
                                            <select name="resolution" class="form-control" style="max-width: 150px; display:inline-block;">
                                                <option value="covered">Ditampung</option>
                                                <option value="waived">Dihapus</option>
                                                <option value="member_topup">Ahli tambah</option>
                                            </select>
                                            <input type="text" name="notes" class="form-control" placeholder="Nota" style="max-width: 140px; display:inline-block;">
                                            <button type="submit" class="btn btn-primary btn-sm">Selesai</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
