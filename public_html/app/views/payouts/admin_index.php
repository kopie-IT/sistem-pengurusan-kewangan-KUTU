<?php
/** @var array $payouts */
/** @var array $plans */
/** @var string|null $planFilter */
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
<?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Urus Payout</h1>
                <p class="muted">Jadual pembayaran ahli dan jana pembayaran.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/payouts/schedule') ?>" class="btn btn-primary">Tetapkan Jadual</a>
            </div>
        </div>

        <form method="GET" action="<?= url('/admin/payouts') ?>" class="toolbar">
            <select name="plan_id" class="form-control" style="max-width: 260px;">
                <option value="">Semua pelan</option>
                <?php foreach ($plans ?? [] as $p): ?>
                    <option value="<?= e((string) $p->id) ?>" <?= ($planFilter ?? '') == $p->id ? 'selected' : '' ?>><?= e($p->name ?? $p->planCode ?? $p->id) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Tapis</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kitar</th>
                        <th>Pelan</th>
                        <th>Penerima</th>
                        <th>Tarikh</th>
                        <th>Jangkaan</th>
                        <th>Status</th>
                        <th class="wrap">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payouts)): ?>
                        <tr><td colspan="7" class="empty-state">Tiada jadual payout dijumpai.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payouts as $p): ?>
                            <tr>
                                <td><?= e((string) ($p->cycle_no ?? $p->cycleNo ?? '-')) ?></td>
                                <td><?= e($p->plan_name ?? $p->recipient_name ?? '-') ?></td>
                                <td><?= e($p->recipient_name ?? $p->recipient_member_id ?? '-') ?></td>
                                <td><?= e($p->payout_date ?? '-') ?></td>
                                <td><?= format_money($p->expected_amount ?? 0) ?></td>
                                <td><span class="badge badge-<?= $statusBadge($p->status ?? null) ?>"><?= e(ucfirst($p->status ?? 'menunggu')) ?></span></td>
                                <td class="wrap">
                                    <a href="<?= url('/admin/payouts/' . $p->id . '/generate') ?>" class="btn btn-ghost btn-sm">Jana Pembayaran</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
