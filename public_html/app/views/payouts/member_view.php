<?php
/** @var array $upcoming */
/** @var array $planNames */
$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'paid' => 'success',
        'processing' => 'info',
        'pending' => 'warning',
        'cancelled' => 'danger',
        default => 'neutral',
    };
};
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pembayaran</span>
                <h1>Jadual Pembayaran Saya</h1>
                <p class="muted">Payout yang akan datang mengikut pelan anda.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">Papan Pemuka</a>
            </div>
        </div>

        <h2 class="card-title mt-4">Jadual Pembayaran Akan Datang</h2>
        <?php if (empty($upcoming)): ?>
            <div class="card">
                <div class="empty-state">Tiada jadual pembayaran akan datang buat masa ini.</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kitar</th>
                            <th>Tarikh</th>
                            <th>Penerima</th>
                            <th>Kasar</th>
                            <th>Bersih</th>
                            <th>Status</th>
                            <th>Slip</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $p): ?>
                            <tr>
                                <td><?= e((string) ($p->cycle_no ?? $p->cycleNo ?? '-')) ?></td>
                                <td><?= e($p->payout_date ?? '-') ?></td>
                                <td><?= e((string) ($p->recipient_member_id ?? '-')) ?></td>
                                <td><?= format_money($p->gross_payout ?? $p->expected_amount ?? 0) ?></td>
                                <td><?= format_money($p->net_payout ?? ($p->gross_payout ?? $p->expected_amount ?? 0)) ?></td>
                                <td><span class="badge badge-<?= $statusBadge($p->status ?? null) ?>"><?= e(ucfirst($p->status ?? 'menunggu')) ?></span></td>
                                <td>
                                    <?php if (($p->status ?? '') === 'paid'): ?>
                                        <a href="<?= url('/file/payout/' . ($p->id ?? '')) ?>" class="btn btn-ghost btn-sm">Lihat Slip</a>
                                    <?php else: ?>
                                        <span class="muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
