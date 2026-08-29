<?php
/** @var array $schedules */
/** @var array $planNames */
$total = 0;
foreach ($schedules ?? [] as $s) {
    $total += ($s->amount ?? 0) - ($s->amount_paid ?? 0);
}
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Bayaran Pukal</span>
                <h1>Bayaran Pukal Caruman</h1>
                <p class="muted">Bayar beberapa jadual caruman serentak dengan satu muat naik slip resit.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/payments') ?>" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    <span>Kembali ke Senarai</span>
                </a>
            </div>
        </div>

        <?php if (empty($schedules)): ?>
            <div class="card">
                <div class="empty-state">Tiada caruman tertunggak untuk dibayar.</div>
            </div>
        <?php else: ?>
            <!-- Summary Stat Cards -->
            <div class="grid grid-3" style="margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary, #3b82f6); background: var(--bg-surface, #fff);">
                    <span class="muted small" style="font-weight: 500;">Bilangan Item Tertunggak</span>
                    <strong style="display: block; font-size: 1.35rem; color: #1e293b; margin-top: 0.25rem;"><?= count($schedules) ?> Jadual</strong>
                    <span class="muted small" style="margin-top: 0.25rem; display: block;">Boleh dipilih serentak</span>
                </div>
                <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981; background: var(--bg-surface, #fff);">
                    <span class="muted small" style="font-weight: 500;">Jumlah Keseluruhan Perlu Dibayar</span>
                    <strong id="bulk-total-display" style="display: block; font-size: 1.35rem; color: #16a34a; margin-top: 0.25rem;"><?= format_money($total) ?></strong>
                    <span class="muted small" style="margin-top: 0.25rem; display: block;">Berdasarkan pilihan di bawah</span>
                </div>
                <div class="card" style="padding: 1.25rem; border-left: 4px solid #f59e0b; background: var(--bg-surface, #fff);">
                    <span class="muted small" style="font-weight: 500;">Kaedah Pengesahan</span>
                    <strong style="display: block; font-size: 1.15rem; color: #d97706; margin-top: 0.25rem;">1 Slip Resit Sah</strong>
                    <span class="muted small" style="margin-top: 0.25rem; display: block;">Untuk semua item bertanda</span>
                </div>
            </div>

            <div class="card" style="padding: 1.5rem; background: var(--bg-surface, #fff);">
                <form method="POST" action="<?= url('/payments/bulk') ?>" enctype="multipart/form-data" novalidate id="bulk-payment-form">
                    <?= csrf_field() ?>

                    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <h2 class="card-title" style="margin: 0; font-size: 1.15rem;">Pilih Jadual Untuk Dibayar</h2>
                        <span class="muted small">Tandakan kotak pilihan untuk sertakan dalam pembayaran ini</span>
                    </div>

                    <div class="table-wrap" style="margin-bottom: 1.5rem;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">Pilih</th>
                                    <th>Pelan</th>
                                    <th>Kitaran</th>
                                    <th>Tarikh Due</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Baki Perlu Dibayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $i => $s): ?>
                                    <?php $balance = ($s->amount ?? 0) - ($s->amount_paid ?? 0); ?>
                                    <tr>
                                        <td style="text-align: center;">
                                            <input type="checkbox" name="items[<?= $i ?>][selected]" value="1" checked class="bulk-item-check" data-amount="<?= e((string) $balance) ?>" style="width: 18px; height: 18px; cursor: pointer;">
                                            <input type="hidden" name="items[<?= $i ?>][schedule_id]" value="<?= e((string) $s->id) ?>">
                                            <input type="hidden" name="items[<?= $i ?>][plan_id]" value="<?= e((string) ($s->planId ?? $s->plan_id ?? '')) ?>">
                                            <input type="hidden" name="items[<?= $i ?>][amount]" value="<?= e((string) $balance) ?>">
                                        </td>
                                        <td><strong><?= e($planNames[$s->planId ?? $s->plan_id ?? ''] ?? $s->planId ?? $s->plan_id ?? '-') ?></strong></td>
                                        <td><span class="badge badge-neutral">Kitaran <?= e((string) ($s->cycle_no ?? $s->cycleNo ?? '-')) ?></span></td>
                                        <td><?= e($s->due_date ?? '-') ?></td>
                                        <td><span class="badge badge-<?= ($s->status ?? '') === 'overdue' ? 'danger' : 'warning' ?>"><?= e(ucfirst($s->status ?? 'belum')) ?></span></td>
                                        <td style="text-align: right;"><strong style="color: #16a34a;"><?= format_money($balance) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="grid grid-2" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e2e8f0; align-items: end;">
                        <div class="form-group" style="margin: 0;">
                            <label for="slip" class="form-label" style="font-weight: 600;">Muat Naik Slip Pembayaran (Satu untuk semua)</label>
                            <input type="file" id="slip" name="slip" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required style="padding: 0.5rem;">
                            <p class="form-help muted small" style="margin-top: 0.35rem;">Format diterima: JPG, JPEG, PNG atau PDF (Maksimum 5MB).</p>
                        </div>

                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;">
                            <div style="text-align: right;">
                                <span class="muted small">Jumlah Bersih Dipilih:</span>
                                <div id="selected-total-val" style="font-size: 1.5rem; font-weight: 700; color: #16a34a;"><?= format_money($total) ?></div>
                            </div>
                            <div style="display: flex; gap: 0.75rem;">
                                <a href="<?= url('/payments') ?>" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary" style="min-width: 180px; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <span>Hantar Bayaran Pukal</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var checkboxes = document.querySelectorAll('.bulk-item-check');
    var totalDisplay = document.getElementById('selected-total-val');
    var bulkTotalDisplay = document.getElementById('bulk-total-display');

    function calculateTotal() {
        var sum = 0;
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                sum += parseFloat(cb.getAttribute('data-amount') || 0);
            }
        });
        var formatted = 'RM ' + sum.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        if (totalDisplay) totalDisplay.textContent = formatted;
        if (bulkTotalDisplay) bulkTotalDisplay.textContent = formatted;
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', calculateTotal);
    });
});
</script>
