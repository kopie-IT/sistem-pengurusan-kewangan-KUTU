<?php
/** @var object $schedule */
/** @var string|null $planName */
$outstanding = ($schedule->amount ?? 0) - ($schedule->amount_paid ?? 0);
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Bayaran</span>
                <h1>Buat Bayaran</h1>
                <p class="muted">Bayar caruman untuk pelan <?= e($planName ?? '-') ?>.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/payments') ?>" class="btn btn-secondary">Kembali</a>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <h2 class="card-title">Butiran Caruman</h2>
                <table class="table">
                    <tbody>
                        <tr><td class="muted">Pelan</td><td><?= e($planName ?? '-') ?></td></tr>
                        <tr><td class="muted">Tarikh Due</td><td><?= e($schedule->due_date ?? '-') ?></td></tr>
                        <tr><td class="muted">Jumlah Penuh</td><td><?= format_money($schedule->amount ?? 0) ?></td></tr>
                        <tr><td class="muted">Dibayar</td><td><?= format_money($schedule->amount_paid ?? 0) ?></td></tr>
                        <tr><td class="muted">Baki Tertunggak</td><td><strong><?= format_money($outstanding) ?></strong></td></tr>
                        <tr><td class="muted">Status</td><td><span class="badge badge-<?= ($schedule->status ?? '') === 'overdue' ? 'danger' : 'warning' ?>"><?= e(ucfirst($schedule->status ?? 'belum')) ?></span></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2 class="card-title">Borang Bayaran</h2>
                <form method="POST" action="<?= url('/payments/single') ?>" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="schedule_id" value="<?= e((string) $schedule->id) ?>">
                    <input type="hidden" name="plan_id" value="<?= e((string) ($schedule->plan_id ?? '')) ?>">

                    <div class="form-group">
                        <label for="amount" class="form-label">Jumlah Bayaran</label>
                        <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0"
                               value="<?= e((string) $outstanding) ?>" required>
                        <p class="form-help">Dipra-isi dengan baki tertunggak.</p>
                    </div>

                    <div class="form-group">
                        <label for="slip" class="form-label">Slip Pembayaran</label>
                        <input type="file" id="slip" name="slip" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        <p class="form-help">Format: JPG, JPEG, PNG atau PDF.</p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">Hantar Bayaran</button>
                </form>
            </div>
        </div>
    </div>
</section>
