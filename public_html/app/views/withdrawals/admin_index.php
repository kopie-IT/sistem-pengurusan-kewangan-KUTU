<?php
/** @var array $withdrawals */
/** @var string|null $statusFilter */
$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'approved' => 'success',
        'rejected' => 'danger',
        'pending'  => 'warning',
        'cancelled'=> 'neutral',
        default => 'neutral',
    };
};
?>
<?= flash_messages() ?>

<div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Permintaan Pengeluaran</h1>
                <p class="muted">Lulus atau tolak permintaan pengeluaran ahli.</p>
            </div>
        </div>

        <form method="GET" action="<?= url('/admin/withdrawals') ?>" class="toolbar">
            <select name="status" class="form-control" style="max-width: 200px;">
                <option value="">Semua status</option>
                <?php foreach (['pending', 'approved', 'rejected', 'cancelled'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($statusFilter ?? '') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Tapis</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ahli</th>
                        <th>Pelan</th>
                        <th>Sebab</th>
                        <th>Tarikh</th>
                        <th>Status</th>
                        <th class="wrap">Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($withdrawals)): ?>
                        <tr><td colspan="6" class="empty-state">Tiada permintaan pengeluaran.</td></tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr>
                                <td><?= e($w->member_name ?? $w->member_id ?? '-') ?></td>
                                <td><?= e($w->plan_name ?? $w->plan_id ?? '-') ?></td>
                                <td><?= e($w->reason ?? '-') ?></td>
                                <td><?= e($w->request_date ?? '-') ?></td>
                                <td><span class="badge badge-<?= $statusBadge($w->status ?? null) ?>"><?= e(ucfirst($w->status ?? 'menunggu')) ?></span></td>
                                <td class="wrap">
                                    <?php if (($w->status ?? '') === 'pending'): ?>
                                        <form method="POST" action="<?= url('/admin/withdrawals/' . $w->id . '/decision') ?>" style="display:inline-block;">
                                            <?= csrf_field() ?>
                                            <select name="status" class="form-control" style="max-width: 140px; display:inline-block;">
                                                <option value="approved">Lulus</option>
                                                <option value="rejected">Tolak</option>
                                            </select>
                                            <input type="text" name="notes" class="form-control" placeholder="Nota" style="max-width: 130px; display:inline-block;">
                                            <button type="submit" class="btn btn-primary btn-sm">Hantar</button>
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
