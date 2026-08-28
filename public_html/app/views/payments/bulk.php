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
                <span class="page-eyebrow">Bayaran</span>
                <h1>Bayaran Pukal</h1>
                <p class="muted">Bayar beberapa caruman tertunggak dengan satu slip.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/payments') ?>" class="btn btn-secondary">Kembali</a>
            </div>
        </div>

        <?php if (empty($schedules)): ?>
            <div class="card">
                <div class="empty-state">Tiada caruman tertunggak untuk dibayar.</div>
            </div>
        <?php else: ?>
            <div class="card">
                <form method="POST" action="<?= url('/payments/bulk') ?>" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>

                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pilih</th>
                                    <th>Pelan</th>
                                    <th>Tarikh Due</th>
                                    <th>Baki</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $i => $s): ?>
                                    <?php $balance = ($s->amount ?? 0) - ($s->amount_paid ?? 0); ?>
                                    <tr>
                                        <td>
                                            <label class="form-check">
                                                <input type="checkbox" name="items[<?= $i ?>][selected]" value="1" checked>
                                                <input type="hidden" name="items[<?= $i ?>][schedule_id]" value="<?= e((string) $s->id) ?>">
                                                <input type="hidden" name="items[<?= $i ?>][plan_id]" value="<?= e((string) ($s->planId ?? $s->plan_id ?? '')) ?>">
                                                <input type="hidden" name="items[<?= $i ?>][amount]" value="<?= e((string) $balance) ?>">
                                            </label>
                                        </td>
                                        <td><?= e($planNames[$s->planId] ?? $s->planId ?? '-') ?></td>
                                        <td><?= e($s->due_date ?? '-') ?></td>
                                        <td><strong><?= format_money($balance) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mt-4">
                        <label for="slip" class="form-label">Slip Pembayaran (Satu untuk semua)</label>
                        <input type="file" id="slip" name="slip" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        <p class="form-help">Format: JPG, JPEG, PNG atau PDF.</p>
                    </div>

                    <div class="flex flex-between mt-3">
                        <p class="muted">Jumlah Keseluruhan: <strong><?= format_money($total) ?></strong></p>
                        <button type="submit" class="btn btn-primary btn-lg">Buat Bayaran Pukal</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
