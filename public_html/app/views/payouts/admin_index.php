<?php
/** @var array $schedules */
/** @var array $plans */
/** @var array $stats */
/** @var int|null $planFilter */
/** @var string|null $status */
/** @var string|null $search */

$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'paid' => 'success',
        'processing' => 'info',
        'scheduled' => 'neutral',
        'due' => 'warning',
        'cancelled' => 'danger',
        default => 'neutral',
    };
};
?>
<?= flash_messages() ?>

<div class="page-header">
    <div>
        <span class="page-eyebrow">Pentadbiran</span>
        <h1>Urus Giliran Payout</h1>
        <p class="muted">Senarai jadual pembayaran dan giliran wang kutu untuk semua ahli.</p>
    </div>
    <div class="actions">
        <a href="<?= url('/admin/payouts/schedule') ?>" class="btn btn-primary">+ Tetapkan Jadual</a>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="grid grid-4" style="margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
    <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary, #3b82f6); background: var(--bg-surface, #fff); display: flex; flex-direction: column; justify-content: space-between; min-height: 110px;">
        <span class="muted small" style="display: block; font-weight: 500;">Jumlah Giliran</span>
        <h2 style="margin: 0.25rem 0 0; font-size: 1.75rem; font-weight: 700; color: #1e293b;"><?= number_format((int) ($stats['total_count'] ?? 0)) ?></h2>
        <span class="muted small" style="display: block; margin-top: 0.25rem;">Keseluruhan giliran pelan</span>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--success, #10b981); background: var(--bg-surface, #fff); display: flex; flex-direction: column; justify-content: space-between; min-height: 110px;">
        <span class="muted small" style="display: block; font-weight: 500;">Telah Dibayar</span>
        <h2 style="margin: 0.25rem 0 0; font-size: 1.75rem; font-weight: 700; color: #16a34a;"><?= number_format((int) ($stats['paid_count'] ?? 0)) ?></h2>
        <span class="muted small" style="display: block; margin-top: 0.25rem;"><?= format_money((string) ($stats['paid_amount'] ?? '0')) ?> selesai</span>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--warning, #f59e0b); background: var(--bg-surface, #fff); display: flex; flex-direction: column; justify-content: space-between; min-height: 110px;">
        <span class="muted small" style="display: block; font-weight: 500;">Dalam Giliran / Menunggu</span>
        <h2 style="margin: 0.25rem 0 0; font-size: 1.75rem; font-weight: 700; color: #d97706;"><?= number_format((int) ($stats['pending_count'] ?? 0)) ?></h2>
        <span class="muted small" style="display: block; margin-top: 0.25rem;"><?= format_money((string) ($stats['pending_amount'] ?? '0')) ?> dalam giliran</span>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--danger, #ef4444); background: var(--bg-surface, #fff); display: flex; flex-direction: column; justify-content: space-between; min-height: 110px;">
        <span class="muted small" style="display: block; font-weight: 500;">Perlu Bayar Segera</span>
        <h2 style="margin: 0.25rem 0 0; font-size: 1.75rem; font-weight: 700; color: #dc2626;"><?= number_format((int) ($stats['due_count'] ?? 0)) ?></h2>
        <span class="muted small" style="display: block; margin-top: 0.25rem;">Tindakan payout hari ini</span>
    </div>
</div>

<form method="GET" action="<?= url('/admin/payouts') ?>" class="toolbar" style="margin-bottom: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
    <input type="text" name="search" class="form-control" placeholder="Cari nama ahli, emel atau kod..." value="<?= e($search ?? '') ?>" style="max-width: 260px;">
    
    <select name="plan_id" class="form-control" style="max-width: 240px;">
        <option value="">Semua Pelan</option>
        <?php foreach ($plans ?? [] as $p): ?>
            <option value="<?= e((string) $p->id) ?>" <?= ($planFilter ?? 0) === (int)$p->id ? 'selected' : '' ?>><?= e($p->name ?? $p->planCode ?? $p->id) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="status" class="form-control" style="max-width: 180px;">
        <option value="">Semua Status</option>
        <option value="scheduled" <?= ($status ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
        <option value="due" <?= ($status ?? '') === 'due' ? 'selected' : '' ?>>Due</option>
        <option value="processing" <?= ($status ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
        <option value="paid" <?= ($status ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
        <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>

    <button type="submit" class="btn btn-secondary">Tapis</button>
    <?php if (!empty($search) || !empty($planFilter) || !empty($status)): ?>
        <a href="<?= url('/admin/payouts') ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
</form>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Kitaran</th>
                <th>Pelan</th>
                <th>Penerima (Ahli)</th>
                <th>Tarikh Giliran</th>
                <th>Jumlah Jangkaan</th>
                <th>Status</th>
                <th class="wrap">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($schedules)): ?>
                <tr><td colspan="8" class="empty-state">Tiada rekod jadual payout dijumpai.</td></tr>
            <?php else: ?>
                <?php foreach ($schedules as $s): ?>
                    <?php 
                        $schedId = is_array($s) ? ($s['id'] ?? 0) : ($s->id ?? 0);
                        $cycleNo = is_array($s) ? ($s['cycle_no'] ?? $s['plan_cycle_id'] ?? '-') : ($s->cycleNo ?? '-');
                        $planName = is_array($s) ? ($s['plan_name'] ?? ('Pelan #' . ($s['plan_id'] ?? ''))) : ($s->planName ?? '-');
                        $recName = is_array($s) ? ($s['recipient_name'] ?? ('Ahli #' . ($s['recipient_member_id'] ?? ''))) : ($s->recipientName ?? '-');
                        $recCode = is_array($s) ? ($s['member_code'] ?? '') : '';
                        $pDate = is_array($s) ? ($s['payout_date'] ?? '-') : ($s->payoutDate ?? '-');
                        $expAmount = is_array($s) ? ($s['expected_amount'] ?? 0) : ($s->expectedAmount ?? 0);
                        $sStatus = is_array($s) ? ($s['status'] ?? 'scheduled') : ($s->status ?? 'scheduled');
                    ?>
                    <tr>
                        <td>#<?= e((string) $schedId) ?></td>
                        <td><span class="badge badge-neutral">Kitaran <?= e((string) $cycleNo) ?></span></td>
                        <td><strong><?= e((string) $planName) ?></strong></td>
                        <td>
                            <div><strong><?= e((string) $recName) ?></strong></div>
                            <?php if (!empty($recCode)): ?>
                                <span class="muted small"><?= e((string) $recCode) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) $pDate) ?></td>
                        <td><strong><?= format_money((string) $expAmount) ?></strong></td>
                        <td><span class="badge badge-<?= $statusBadge($sStatus) ?>"><?= e(ucfirst((string) $sStatus)) ?></span></td>
                        <td class="wrap">
                            <?php if ($sStatus !== 'paid'): ?>
                                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('payout-modal-<?= $schedId ?>').style.display='block'">Jana Payout</button>
                            <?php else: ?>
                                <span class="muted small">Selesai</span>
                            <?php endif; ?>

                            <!-- Payout Modal Dialog -->
                            <div id="payout-modal-<?= $schedId ?>" class="modal" style="display:none; position: fixed; z-index: 999; inset: 0; background: rgba(0,0,0,0.5); padding: 2rem;">
                                <div class="card" style="max-width: 480px; margin: 5% auto; background: var(--bg-surface, #fff); padding: 1.5rem;">
                                    <h3>Jana Bayaran Payout</h3>
                                    <p class="muted small">Kitaran <?= e((string) $cycleNo) ?> - <?= e((string) $planName) ?></p>
                                    <p>Penerima: <strong><?= e((string) $recName) ?></strong></p>
                                    <form method="POST" action="<?= url('/admin/payouts/' . $schedId . '/generate') ?>" enctype="multipart/form-data">
                                        <?= csrf_field() ?>
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <label class="form-label">Jumlah Kutipan Sebenar (RM)</label>
                                            <input type="number" step="0.01" name="actual_collection" class="form-control" value="<?= e((string) $expAmount) ?>" required>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <label class="form-label">Slip Bayaran (Pilihan)</label>
                                            <input type="file" name="slip" class="form-control" accept="image/*,application/pdf">
                                        </div>
                                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('payout-modal-<?= $schedId ?>').style.display='none'">Batal</button>
                                            <button type="submit" class="btn btn-primary">Sahkan Payout</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
