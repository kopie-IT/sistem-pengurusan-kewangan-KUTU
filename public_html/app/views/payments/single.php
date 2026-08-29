<?php
/** @var object $schedule */
/** @var string|null $planName */
$outstanding = ($schedule->amount ?? 0) - ($schedule->amount_paid ?? 0);
$planDisplayName = $planName ?? $schedule->plan_name ?? $schedule->plan_code ?? 'Pelan Kutu #' . ($schedule->plan_id ?? '-');
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Bayaran Caruman</span>
                <h1>Buat Bayaran</h1>
                <p class="muted">Hantar bukti pembayaran caruman bagi kitaran yang ditetapkan.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/payments') ?>" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    <span>Kembali ke Senarai</span>
                </a>
            </div>
        </div>

        <!-- Summary Stat Cards -->
        <div class="grid grid-3" style="margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
            <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary, #3b82f6); background: var(--bg-surface, #fff);">
                <span class="muted small" style="font-weight: 500;">Pelan Kutu</span>
                <strong style="display: block; font-size: 1.15rem; color: #1e293b; margin-top: 0.25rem;"><?= e($planDisplayName) ?></strong>
                <span class="muted small" style="margin-top: 0.25rem; display: block;">Kitaran <?= e((string) ($schedule->cycle_no ?? '-')) ?></span>
            </div>
            <div class="card" style="padding: 1.25rem; border-left: 4px solid #f59e0b; background: var(--bg-surface, #fff);">
                <span class="muted small" style="font-weight: 500;">Tarikh Akhir (Due Date)</span>
                <strong style="display: block; font-size: 1.15rem; color: #d97706; margin-top: 0.25rem;"><?= e($schedule->due_date ?? '-') ?></strong>
                <span class="muted small" style="margin-top: 0.25rem; display: block;">Status: <span class="badge badge-<?= ($schedule->status ?? '') === 'overdue' ? 'danger' : 'warning' ?>"><?= e(ucfirst($schedule->status ?? 'belum')) ?></span></span>
            </div>
            <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981; background: var(--bg-surface, #fff);">
                <span class="muted small" style="font-weight: 500;">Baki Perlu Dibayar</span>
                <strong style="display: block; font-size: 1.35rem; color: #16a34a; margin-top: 0.25rem;"><?= format_money($outstanding) ?></strong>
                <span class="muted small" style="margin-top: 0.25rem; display: block;">Jumlah asal: <?= format_money($schedule->amount ?? 0) ?></span>
            </div>
        </div>

        <div class="grid grid-2" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; align-items: start;">
            <!-- Left Column: Details & Payment Instruction -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="card" style="padding: 1.5rem; background: var(--bg-surface, #fff);">
                    <h2 class="card-title" style="margin-top: 0; font-size: 1.15rem; margin-bottom: 1rem;">Butiran Caruman</h2>
                    <table class="table" style="margin: 0;">
                        <tbody>
                            <tr>
                                <td class="muted" style="width: 40%;">Nama Pelan</td>
                                <td><strong><?= e($planDisplayName) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="muted">Nombor Kitaran</td>
                                <td><span class="badge badge-neutral">Kitaran <?= e((string) ($schedule->cycle_no ?? '-')) ?></span></td>
                            </tr>
                            <tr>
                                <td class="muted">Tarikh Akhir (Due)</td>
                                <td><?= e($schedule->due_date ?? '-') ?></td>
                            </tr>
                            <tr>
                                <td class="muted">Jumlah Penuh Kitaran</td>
                                <td><?= format_money($schedule->amount ?? 0) ?></td>
                            </tr>
                            <tr>
                                <td class="muted">Telah Dibayar</td>
                                <td><?= format_money($schedule->amount_paid ?? 0) ?></td>
                            </tr>
                            <tr style="background: #f8fafc;">
                                <td class="muted" style="font-weight: 600;">Baki Tertunggak</td>
                                <td><strong style="color: #16a34a; font-size: 1.1rem;"><?= format_money($outstanding) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="muted">Status Caruman</td>
                                <td><span class="badge badge-<?= ($schedule->status ?? '') === 'overdue' ? 'danger' : 'warning' ?>"><?= e(ucfirst($schedule->status ?? 'belum')) ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Payment Instruction Card -->
                <div class="card" style="padding: 1.25rem 1.5rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                    <h3 style="margin: 0 0 0.5rem; font-size: 1rem; font-weight: 600; color: #166534; display: flex; align-items: center; gap: 0.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <span>Panduan Pembayaran</span>
                    </h3>
                    <ul class="small" style="margin: 0; padding-left: 1.25rem; color: #15803d; line-height: 1.6;">
                        <li>Pindahkan wang caruman ke akaun rasmi pengurusan kutu atau imbas Kod QR.</li>
                        <li>Simpan resit/slip transaksi dalam format imej (JPG/PNG) atau PDF.</li>
                        <li>Muat naik slip pembayaran di borang sebelah kanan untuk pengesahan pentadbir.</li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Submission Form -->
            <div class="card" style="padding: 1.5rem; background: var(--bg-surface, #fff);">
                <div style="margin-bottom: 1.25rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;">
                    <h2 class="card-title" style="margin: 0; font-size: 1.15rem;">Borang Penghantaran Bayaran</h2>
                    <p class="muted small" style="margin-top: 0.25rem;">Masukkan jumlah dan lampirkan bukti pembayaran.</p>
                </div>

                <form method="POST" action="<?= url('/payments/single') ?>" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="schedule_id" value="<?= e((string) $schedule->id) ?>">
                    <input type="hidden" name="plan_id" value="<?= e((string) ($schedule->plan_id ?? '')) ?>">

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="amount" class="form-label" style="font-weight: 600;">Jumlah Bayaran (RM)</label>
                        <div style="position: relative;">
                            <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0.01"
                                   value="<?= e((string) $outstanding) ?>" required style="font-size: 1.1rem; font-weight: 600; padding: 0.65rem 0.85rem;">
                        </div>
                        <p class="form-help muted small" style="margin-top: 0.35rem;">Dipra-isi dengan baki tertunggak RM <?= number_format((float) $outstanding, 2) ?>.</p>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="slip" class="form-label" style="font-weight: 600;">Muat Naik Slip Pembayaran</label>
                        <input type="file" id="slip" name="slip" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required style="padding: 0.5rem;">
                        <p class="form-help muted small" style="margin-top: 0.35rem;">Format diterima: JPG, JPEG, PNG, atau PDF (Maksimum 5MB).</p>
                    </div>

                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                        <a href="<?= url('/payments') ?>" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary" style="min-width: 160px; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span>Hantar Bayaran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
