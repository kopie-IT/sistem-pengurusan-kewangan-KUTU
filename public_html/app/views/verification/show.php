<?php
/** @var object $batch */
/** @var array $items */
/** @var object|null $slip */
$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'approved' => 'success',
        'rejected' => 'danger',
        'resubmit' => 'warning',
        'pending'  => 'warning',
        default => 'neutral',
    };
};
?>
<?= flash_messages() ?>

<div class="page-header">
            <div>
                <span class="page-eyebrow">Pengesahan</span>
                <h1>Kumpulan <?= e($batch->batch_no ?? '-') ?></h1>
                <p class="muted">Ahli: <?= e($batch->member_name ?? $batch->member_id ?? '-') ?></p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/payments') ?>" class="btn btn-secondary">Kembali</a>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <h2 class="card-title">Butiran Kumpulan</h2>
                <table class="table">
                    <tbody>
                        <tr><td class="muted">No. Kumpulan</td><td><?= e($batch->batch_no ?? '-') ?></td></tr>
                        <tr><td class="muted">Ahli</td><td><?= e($batch->member_name ?? $batch->member_id ?? '-') ?></td></tr>
                        <tr><td class="muted">Jumlah</td><td><strong><?= format_money($batch->total_amount ?? 0) ?></strong></td></tr>
                        <tr><td class="muted">Status</td><td><span class="badge badge-<?= $statusBadge($batch->status ?? null) ?>"><?= e(ucfirst($batch->status ?? 'menunggu')) ?></span></td></tr>
                        <tr><td class="muted">Nota</td><td><?= e($batch->note ?? '-') ?></td></tr>
                    </tbody>
                </table>
                <?php if (!empty($slip)): ?>
                    <p class="mt-3">
                        <a href="<?= url('/file/slip/' . ($slip->id ?? '')) ?>" class="btn btn-secondary btn-sm">
                            Lihat Slip (<?= e($slip->original_name ?? 'fail') ?>)
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2 class="card-title">Tindakan</h2>
                <?php if (in_array($batch->status ?? '', ['submitted', 'pending_verification', 'pending', 'resubmit', 'resubmission'], true)): ?>
                    <form method="POST" action="<?= url('/admin/payments/' . $batch->id . '/approve') ?>" style="margin-bottom: 1rem;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-block">Lulus</button>
                    </form>

                    <form method="POST" action="<?= url('/admin/payments/' . $batch->id . '/reject') ?>">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="note" class="form-label">Sebab Tolak</label>
                            <textarea id="note" name="note" class="form-control" rows="2" placeholder="Nyatakan sebab"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block">Tolak</button>
                    </form>

                    <form method="POST" action="<?= url('/admin/payments/' . $batch->id . '/resubmit') ?>" style="margin-top: 1rem;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-ghost btn-block">Minta Hantar Semula</button>
                    </form>
                <?php else: ?>
                    <p class="muted">Kumpulan ini telah diproses (<?= e(ucfirst($batch->status ?? '-')) ?>).</p>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="card-title mt-5">Pecahan Peruntukan</h2>
        <?php if (empty($items)): ?>
            <div class="card">
                <div class="empty-state">Tiada item dalam kumpulan ini.</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pelan</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= e($it->plan_name ?? $it->plan_id ?? '-') ?></td>
                                <td><?= format_money($it->amount ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
