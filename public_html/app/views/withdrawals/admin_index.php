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
                                        <form method="POST" action="<?= url('/admin/withdrawals/' . $w->id . '/decision') ?>" style="display:inline-flex; align-items: center; gap: 0.35rem;">
                                            <?= csrf_field() ?>
                                            <select name="status" class="form-control" style="max-width: 110px; display:inline-block; font-size: 0.8125rem; padding: 0.35rem 0.5rem;">
                                                <option value="approved">Lulus</option>
                                                <option value="rejected">Tolak</option>
                                            </select>
                                            <input type="text" name="notes" class="form-control" placeholder="Nota" style="max-width: 110px; display:inline-block; font-size: 0.8125rem; padding: 0.35rem 0.5rem;">
                                            <button type="submit" class="btn btn-primary btn-sm btn-icon" title="Hantar Keputusan" aria-label="Hantar Keputusan">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </button>
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
