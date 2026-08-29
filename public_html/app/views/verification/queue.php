<?php
/** @var array $batches */
/** @var array $stats */
/** @var string|null $status */
/** @var string|null $search */

$statusBadge = function (?string $st): array {
    return match ($st ?? '') {
        'approved' => ['class' => 'badge-success', 'label' => 'Diluluskan'],
        'rejected' => ['class' => 'badge-danger', 'label' => 'Ditolak'],
        'resubmit', 'resubmission_requested' => ['class' => 'badge-warning', 'label' => 'Hantar Semula'],
        'pending_verification', 'submitted', 'pending' => ['class' => 'badge-warning', 'label' => 'Menunggu Pengesahan'],
        default => ['class' => 'badge-neutral', 'label' => ucfirst($st ?? 'menunggu')],
    };
};
?>
<?= flash_messages() ?>

<div class="page-header">
    <div>
        <span class="page-eyebrow">Pengurusan Kewangan</span>
        <h1>Pengurusan Bayaran &amp; Pengesahan</h1>
        <p class="muted">Semua transaksi bayaran ahli, slip resit dan pengesahan caruman.</p>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="grid grid-4" style="margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
    <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary, #3b82f6); background: var(--bg-surface, #fff);">
        <span class="muted small" style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Jumlah Transaksi</span>
        <h2 style="margin: 0; font-size: 1.75rem; font-weight: 700; color: #1e293b;"><?= number_format((int) ($stats['total_count'] ?? count($batches))) ?></h2>
        <span class="muted small" style="display: block; margin-top: 0.25rem;">Keseluruhan bayaran</span>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--warning, #f59e0b); background: var(--bg-surface, #fff);">
        <span class="muted small" style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Menunggu Pengesahan</span>
        <h2 style="margin: 0; font-size: 1.75rem; font-weight: 700; color: #d97706;"><?= number_format((int) ($stats['pending_count'] ?? 0)) ?></h2>
        <span class="muted small" style="display: block; margin-top: 0.25rem;"><?= format_money($stats['pending_amount'] ?? 0) ?> perlu disemak</span>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--success, #10b981); background: var(--bg-surface, #fff);">
        <span class="muted small" style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Diluluskan</span>
        <h2 style="margin: 0; font-size: 1.75rem; font-weight: 700; color: #16a34a;"><?= number_format((int) ($stats['approved_count'] ?? 0)) ?></h2>
        <span class="muted small" style="display: block; margin-top: 0.25rem;"><?= format_money($stats['approved_amount'] ?? 0) ?> selesai</span>
    </div>
    <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--danger, #ef4444); background: var(--bg-surface, #fff);">
        <span class="muted small" style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Ditolak</span>
        <h2 style="margin: 0; font-size: 1.75rem; font-weight: 700; color: #dc2626;"><?= number_format((int) ($stats['rejected_count'] ?? 0)) ?></h2>
        <span class="muted small" style="display: block; margin-top: 0.25rem;">Perlu tindakan</span>
    </div>
</div>

<!-- Filter Toolbar -->
<form method="GET" action="<?= url('/admin/payments') ?>" class="toolbar mb-4">
    <div class="form-group" style="margin-bottom: 0; min-width: 260px; flex: 1;">
        <input type="text" name="search" class="form-control" placeholder="Cari no kumpulan, nama ahli, emel atau kod ahli..." value="<?= e($search ?? '') ?>">
    </div>
    <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="pending_verification" <?= ($status ?? '') === 'pending_verification' ? 'selected' : '' ?>>Menunggu Pengesahan</option>
            <option value="approved" <?= ($status ?? '') === 'approved' ? 'selected' : '' ?>>Diluluskan</option>
            <option value="rejected" <?= ($status ?? '') === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
            <option value="resubmit" <?= ($status ?? '') === 'resubmit' ? 'selected' : '' ?>>Hantar Semula</option>
        </select>
    </div>
    <button type="submit" class="btn btn-secondary">Tapis</button>
    <?php if (!empty($search) || !empty($status)): ?>
        <a href="<?= url('/admin/payments') ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
</form>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Kumpulan &amp; Ahli</th>
                <th>Jumlah Bayaran</th>
                <th>Slip Bayaran</th>
                <th>Tarikh Dihantar</th>
                <th>Status</th>
                <th class="wrap">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($batches)): ?>
                <tr>
                    <td colspan="6" class="empty-state" style="text-align:center; padding: 2.5rem 1rem;">
                        <p style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 600;">Tiada transaksi bayaran dijumpai.</p>
                        <p class="muted small" style="margin: 0;">Cuba tukar penapis status atau kata carian di atas.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($batches as $b):
                    $bId = is_array($b) ? $b['id'] : $b->id;
                    $batchNo = is_array($b) ? ($b['batch_no'] ?? '-') : ($b->batchNo ?? $b->batch_no ?? '-');
                    $mName = is_array($b) ? ($b['member_name'] ?? ('Ahli #' . ($b['member_id'] ?? ''))) : ($b->member_name ?? ('Ahli #' . ($b->memberId ?? '')));
                    $mPhone = is_array($b) ? ($b['member_phone'] ?? '') : ($b->member_phone ?? '');
                    $mCode = is_array($b) ? ($b['member_code'] ?? '') : ($b->member_code ?? '');
                    $totalAmt = is_array($b) ? ($b['total_amount'] ?? 0) : ($b->totalAmount ?? $b->total_amount ?? 0);
                    $slipId = is_array($b) ? ($b['payment_slip_id'] ?? null) : ($b->paymentSlipId ?? $b->payment_slip_id ?? null);
                    $slipName = is_array($b) ? ($b['slip_original_name'] ?? $b['slip_file_name'] ?? null) : null;
                    $st = is_array($b) ? ($b['status'] ?? 'pending') : ($b->status ?? 'pending');
                    $createdAt = is_array($b) ? ($b['created_at'] ?? '-') : ($b->createdAt ?? $b->created_at ?? '-');
                    $badgeInfo = $statusBadge($st);
                ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <strong style="font-family: monospace; font-size: 0.95rem; color: var(--primary, #2563eb);"><?= e($batchNo) ?></strong>
                                <?php if ($mCode): ?>
                                    <span class="badge badge-neutral" style="font-size: 0.75rem;"><?= e($mCode) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-weight: 600; color: #1e293b;"><?= e($mName) ?></div>
                            <?php if ($mPhone): ?>
                                <div class="muted small" style="display: flex; align-items: center; gap: 0.25rem; margin-top: 2px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                    <?= e($mPhone) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="font-size: 1.05rem;"><?= format_money($totalAmt) ?></strong>
                        </td>
                        <td>
                            <?php if ($slipId): ?>
                                <button type="button" class="btn btn-ghost btn-sm btn-icon" onclick="openSlipModal('<?= url('/file/slip/' . $slipId) ?>', '<?= e($batchNo) ?>', '<?= e($mName) ?>')" title="Lihat Slip Bayaran" aria-label="Lihat Slip Bayaran">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                </button>
                            <?php else: ?>
                                <span class="muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= e(date('d/m/Y', strtotime((string)$createdAt))) ?></div>
                            <div class="muted small"><?= e(date('H:i', strtotime((string)$createdAt))) ?></div>
                        </td>
                        <td>
                            <span class="badge <?= $badgeInfo['class'] ?>"><?= e($badgeInfo['label']) ?></span>
                        </td>
                        <td class="wrap">
                            <div class="table-actions">
                                <a href="<?= url('/admin/payments/' . $bId) ?>" class="btn <?= in_array($st, ['pending_verification', 'submitted', 'pending'], true) ? 'btn-primary' : 'btn-secondary' ?> btn-sm btn-icon" title="<?= in_array($st, ['pending_verification', 'submitted', 'pending'], true) ? 'Sahkan Bayaran' : 'Lihat Butiran Bayaran' ?>" aria-label="<?= in_array($st, ['pending_verification', 'submitted', 'pending'], true) ? 'Sahkan Bayaran' : 'Lihat Butiran Bayaran' ?>">
                                    <?php if (in_array($st, ['pending_verification', 'submitted', 'pending'], true)): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Popup Slip Bayaran -->
<div id="slipModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem; backdrop-filter: blur(4px);">
    <div class="card" style="background: #fff; width: 100%; max-width: 600px; border-radius: 12px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); display: flex; flex-direction: column; max-height: 90vh;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; color: #1e293b;" id="slipModalTitle">Slip Bayaran</h3>
                <span class="muted small" id="slipModalSubtitle"></span>
            </div>
            <button type="button" onclick="closeSlipModal()" style="background: transparent; border: 0; font-size: 1.5rem; cursor: pointer; color: #64748b; line-height: 1; padding: 0.25rem 0.5rem; border-radius: 4px;" aria-label="Tutup">&times;</button>
        </div>
        <div style="padding: 1.5rem; overflow-y: auto; text-align: center; background: #0f172a; flex: 1; display: flex; align-items: center; justify-content: center; min-height: 350px;">
            <img id="slipModalImage" src="" alt="Slip Bayaran" style="max-width: 100%; max-height: 65vh; object-fit: contain; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5);">
            <iframe id="slipModalFrame" src="" style="width: 100%; height: 65vh; border: 0; display: none; background: #fff; border-radius: 6px;"></iframe>
        </div>
        <div style="padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <a id="slipModalDirectLink" href="#" target="_blank" class="btn btn-secondary btn-sm" style="display: flex; align-items: center; gap: 0.25rem;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                Buka Tab Baharu
            </a>
            <button type="button" onclick="closeSlipModal()" class="btn btn-primary btn-sm">Tutup</button>
        </div>
    </div>
</div>

<script>
function openSlipModal(url, batchNo, memberName) {
    const modal = document.getElementById('slipModal');
    const img = document.getElementById('slipModalImage');
    const frame = document.getElementById('slipModalFrame');
    const title = document.getElementById('slipModalTitle');
    const subtitle = document.getElementById('slipModalSubtitle');
    const link = document.getElementById('slipModalDirectLink');

    title.textContent = 'Slip Bayaran: ' + batchNo;
    subtitle.textContent = 'Ahli: ' + memberName;
    link.href = url;

    // Check extension
    if (url.toLowerCase().endsWith('.pdf')) {
        img.style.display = 'none';
        frame.style.display = 'block';
        frame.src = url;
    } else {
        frame.style.display = 'none';
        img.style.display = 'block';
        img.src = url;
    }

    modal.style.display = 'flex';
}

function closeSlipModal() {
    const modal = document.getElementById('slipModal');
    const img = document.getElementById('slipModalImage');
    const frame = document.getElementById('slipModalFrame');
    modal.style.display = 'none';
    img.src = '';
    frame.src = '';
}

// Close on backdrop click
document.getElementById('slipModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeSlipModal();
    }
});
</script>
