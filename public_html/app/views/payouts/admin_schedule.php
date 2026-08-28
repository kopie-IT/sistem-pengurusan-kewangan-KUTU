<?php
/** @var array $plans */
/** @var ?\App\Models\Plan $plan */
/** @var array $members */
?>
<section class="page-header">
    <div>
        <span class="page-eyebrow">Kewangan</span>
        <h1 class="page-title">Tambah Jadual Payout</h1>
        <p class="page-subtitle">Sila pilih pelan, penerima, tarikh payout, dan jumlah yang dijangka.</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('/admin/payouts') ?>" class="btn btn-secondary">&larr; Kembali ke Payout</a>
    </div>
</section>

<?= flash_messages() ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/payouts/schedule') ?>" class="form-grid" novalidate>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="plan_id" class="form-label">Pelan</label>
                <select id="plan_id" name="plan_id" required class="form-control">
                    <option value="">-- Pilih pelan --</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= (int) $p->id ?>" <?= ($plan && $plan->id === $p->id) ? 'selected' : '' ?>>
                            <?= e($p->name) ?> (<?= e($p->planCode) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="cycle_no" class="form-label">Kitaran</label>
                <input type="number" min="1" max="999" id="cycle_no" name="cycle_no" required
                       value="1" class="form-control" placeholder="cth: 1">
            </div>

            <div class="form-group">
                <label for="recipient_member_id" class="form-label">Ahli Penerima</label>
                <select id="recipient_member_id" name="recipient_member_id" required class="form-control">
                    <option value="">-- Pilih ahli --</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m->id ?>"><?= e($m->memberCode) ?> — <?= e($m->phone ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($plan === null): ?>
                    <p class="form-help">Pilih pelan dahulu untuk memuatkan senarai ahli pelan tersebut.</p>
                <?php else: ?>
                    <p class="form-help">Hanya ahli aktif dipaparkan. Pilih pelan lain dari menu di atas untuk menukar.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="payout_date" class="form-label">Tarikh Payout</label>
                <input type="date" id="payout_date" name="payout_date" required
                       value="<?= e(date('Y-m-d')) ?>" class="form-control">
            </div>

            <div class="form-group">
                <label for="expected_amount" class="form-label">Jumlah Dijangka (RM)</label>
                <input type="number" step="0.01" min="0" id="expected_amount" name="expected_amount" required
                       class="form-control" placeholder="cth: 500.00">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Jadual</button>
                <a href="<?= url('/admin/payouts') ?>" class="btn btn-ghost">Batal</a>
            </div>
        </form>
    </div>
</div>
