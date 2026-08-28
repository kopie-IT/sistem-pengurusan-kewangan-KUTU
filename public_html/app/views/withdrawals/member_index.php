<?php
/** @var array $withdrawals */
/** @var array $planNames */
$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'approved' => 'success',
        'rejected' => 'danger',
        'pending'  => 'warning',
        'cancelled' => 'neutral',
        default => 'neutral',
    };
};
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pengeluaran</span>
                <h1>Permintaan Pengeluaran Saya</h1>
                <p class="muted">Status permintaan pengeluaran yang telah dihantar.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/withdrawals/request') ?>" class="btn btn-primary">Mohon Pengeluaran</a>
            </div>
        </div>

        <?php if (empty($withdrawals)): ?>
            <div class="card">
                <div class="empty-state">Anda belum menghantar sebarang permintaan pengeluaran.</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelan</th>
                            <th>Sebab</th>
                            <th>Tarikh Mohon</th>
                            <th>Tarikh Keputusan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr>
                                <td><?= e((string) $w->id) ?></td>
                                <td><?= e($planNames[$w->plan_id] ?? $w->plan_id ?? '-') ?></td>
                                <td><?= e($w->reason ?? '-') ?></td>
                                <td><?= e($w->request_date ?? '-') ?></td>
                                <td><?= e($w->decision_date ?? '-') ?></td>
                                <td><span class="badge badge-<?= $statusBadge($w->status ?? null) ?>"><?= e(ucfirst($w->status ?? 'menunggu')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
