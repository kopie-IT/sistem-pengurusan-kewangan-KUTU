<?php
/** @var object|null $plan */
/** @var string $title */
$isEdit = !empty($plan);
$action = $isEdit ? url('/admin/plans/' . $plan->id) : url('/admin/plans');
$val = fn(string $k) => old($k, $plan->$k ?? '');
?>
<?= flash_messages() ?>

<div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1><?= e($title) ?></h1>
                <p class="muted"><?= $isEdit ? 'Kemaskini butiran pelan simpanan.' : 'Cipta pelan simpanan Main Kutu baharu.' ?></p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/plans') ?>" class="btn btn-secondary">Kembali</a>
            </div>
        </div>

        <div class="card">
            <form method="POST" action="<?= $action ?>" novalidate>
                <?= csrf_field() ?>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="plan_code" class="form-label">Kod Pelan</label>
                        <input type="text" id="plan_code" name="plan_code" class="form-control"
                               value="<?= e($val('planCode')) ?>" placeholder="Contoh: MK-2026-01">
                    </div>

                    <div class="form-group">
                        <label for="name" class="form-label">Nama Pelan</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e($val('name')) ?>" placeholder="Nama pelan">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Keterangan</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Keterangan ringkas pelan"><?= e($val('description')) ?></textarea>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="number_of_members" class="form-label">Bilangan Ahli</label>
                        <input type="number" id="number_of_members" name="number_of_members" class="form-control" min="1"
                               value="<?= e($val('numberOfMembers')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="contribution_amount" class="form-label">Jumlah Caruman</label>
                        <input type="number" id="contribution_amount" name="contribution_amount" class="form-control" step="0.01" min="0"
                               value="<?= e($val('contributionAmount')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="payment_frequency" class="form-label">Kekerapan Pembayaran</label>
                        <select id="payment_frequency" name="payment_frequency" class="form-control">
                            <?php foreach (['weekly', 'biweekly', 'monthly', 'quarterly'] as $f): ?>
                                <option value="<?= e($f) ?>" <?= $val('paymentFrequency') === $f ? 'selected' : '' ?>><?= e(ucfirst($f)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="number_of_cycles" class="form-label">Bilangan Kitaran</label>
                        <input type="number" id="number_of_cycles" name="number_of_cycles" class="form-control" min="1"
                               value="<?= e($val('numberOfCycles')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="start_date" class="form-label">Tarikh Mula</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                               value="<?= e($val('startDate')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="end_date" class="form-label">Tarikh Tamat</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                               value="<?= e($val('endDate')) ?>">
                    </div>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-control">
                            <?php foreach (['draft', 'open', 'full', 'active', 'suspended', 'completed', 'cancelled'] as $s): ?>
                                <option value="<?= e($s) ?>" <?= $val('status') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="max_members" class="form-label">Had Ahli Maksimum</label>
                        <input type="number" id="max_members" name="max_members" class="form-control" min="1"
                               value="<?= e($val('maxMembers')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="min_credit_score" class="form-label">Skor Kredit Minimum</label>
                        <input type="number" id="min_credit_score" name="min_credit_score" class="form-control" min="0" max="100"
                               value="<?= e($val('minCreditScore')) ?>">
                    </div>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="payout_mode" class="form-label">Mod Pembayaran</label>
                        <select id="payout_mode" name="payout_mode" class="form-control">
                            <option value="fixed" <?= $val('payoutMode') === 'fixed' ? 'selected' : '' ?>>Tetap</option>
                            <option value="actual_collection" <?= $val('payoutMode') === 'actual_collection' ? 'selected' : '' ?>>Kutipan Sebenar</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fixed_payout_amount" class="form-label">Jumlah Pembayaran Tetap</label>
                        <input type="number" id="fixed_payout_amount" name="fixed_payout_amount" class="form-control" step="0.01" min="0"
                               value="<?= e($val('fixedPayoutAmount')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="payout_frequency" class="form-label">Kekerapan Pembayaran</label>
                        <select id="payout_frequency" name="payout_frequency" class="form-control">
                            <?php foreach (['weekly', 'biweekly', 'monthly', 'quarterly'] as $f): ?>
                                <option value="<?= e($f) ?>" <?= $val('payoutFrequency') === $f ? 'selected' : '' ?>><?= e(ucfirst($f)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="payout_day" class="form-label">Hari Pembayaran</label>
                        <input type="number" id="payout_day" name="payout_day" class="form-control" min="1" max="31"
                               value="<?= e($val('payoutDay')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="min_score" class="form-label">Skor Minimum (Lain-lain)</label>
                        <input type="number" id="min_score" name="min_score" class="form-control" min="0" max="100"
                               value="<?= e($val('minScore')) ?>">
                    </div>
                </div>

                <div class="flex flex-wrap" style="gap: 1.25rem;">
                    <label class="form-check">
                        <input type="checkbox" name="approval_required" value="1" <?= $val('approvalRequired') ? 'checked' : '' ?>>
                        <span>Kelulusan Diperlukan</span>
                    </label>
                    <label class="form-check">
                        <input type="checkbox" name="allow_multiple" value="1" <?= $val('allowMultiple') ? 'checked' : '' ?>>
                        <span>Benarkan Pelbagai Penyertaan</span>
                    </label>
                    <label class="form-check">
                        <input type="checkbox" name="withdrawal_allowed" value="1" <?= $val('withdrawalAllowed') ? 'checked' : '' ?>>
                        <span>Pengeluaran Dibenarkan</span>
                    </label>
                </div>

                <div class="flex flex-wrap mt-4" style="gap: 0.75rem;">
                    <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Kemaskini Pelan' : 'Simpan Pelan' ?></button>
                    <a href="<?= url('/admin/plans') ?>" class="btn btn-ghost btn-lg">Batal</a>
                </div>
            </form>
        </div>

        <?php if ($isEdit): ?>
            <?php
                $systemQr = (new \App\Repositories\AppSettingRepository())->get('payment_qr_path');
                $planQr   = $plan->paymentQrPath ?? null;
            ?>
            <div class="card mt-4">
                <div class="card-body">
                    <h2 class="card-heading">QR Pembayaran Pelan</h2>
                    <p class="muted">Ahli akan melihat QR ini pada halaman pelan untuk membuat pembayaran dan muat naik slip.</p>

                    <div class="qr-uploader">
                        <div class="qr-preview" aria-live="polite">
                            <?php if ($planQr): ?>
                                <img src="<?= url('/plans/' . $plan->id . '/qr') ?>" alt="QR pelan semasa">
                            <?php elseif ($systemQr): ?>
                                <img src="<?= url('/brand/qr') ?>" alt="QR sistem semasa">
                            <?php else: ?>
                                <span class="qr-empty">Tiada QR ditetapkan</span>
                            <?php endif; ?>
                        </div>
                        <div class="qr-fields">
                            <form method="POST" action="<?= url('/admin/plans/' . $plan->id . '/qr') ?>" enctype="multipart/form-data" novalidate>
                                <?= csrf_field() ?>
                                <div class="form-group">
                                    <label class="form-label" for="payment_qr_<?= (int) $plan->id ?>">Muat Naik QR Baharu</label>
                                    <input type="file" id="payment_qr_<?= (int) $plan->id ?>" name="payment_qr"
                                           accept="image/png,image/jpeg,image/svg+xml,image/webp" class="form-control">
                                    <p class="form-help">PNG, JPG, SVG, WEBP. Maksimum 2 MB.</p>
                                </div>
                                <div class="flex flex-wrap" style="gap: 0.5rem; align-items: center;">
                                    <button type="submit" class="btn btn-primary">Simpan QR</button>
                                    <?php if ($planQr): ?>
                                        <button type="submit" name="remove_qr" value="1" class="btn btn-ghost"
                                                onclick="return confirm('Buang QR pelan ini?');">Buang QR</button>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$planQr && $systemQr): ?>
                                    <p class="form-help" style="margin-top:0.5rem;">Pelan ini belum ada QR khusus — ahli akan nampak QR sistem.</p>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
