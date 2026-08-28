<?php
/** @var array $batches */
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
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Baris Giliran Pengesahan</h1>
                <p class="muted">Kumpulan bayaran menunggu pengesahan.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No. Kumpulan</th>
                        <th>Ahli</th>
                        <th>Jumlah</th>
                        <th>Tarikh</th>
                        <th>Status</th>
                        <th class="wrap">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($batches)): ?>
                        <tr><td colspan="6" class="empty-state">Tiada kumpulan menunggu pengesahan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                            <tr>
                                <td><?= e($b->batch_no ?? '-') ?></td>
                                <td><?= e($b->member_name ?? $b->member_id ?? '-') ?></td>
                                <td><?= format_money($b->total_amount ?? 0) ?></td>
                                <td><?= e($b->created_at ?? '-') ?></td>
                                <td><span class="badge badge-<?= $statusBadge($b->status ?? null) ?>"><?= e(ucfirst($b->status ?? 'menunggu')) ?></span></td>
                                <td class="wrap">
                                    <a href="<?= url('/admin/payments/' . $b->id) ?>" class="btn btn-secondary btn-sm">Lihat</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
