<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow"><?= e($plan->planCode) ?></span>
                <h1><?= e($plan->name) ?></h1>
                <p class="muted">Butiran pelan dan kelayakan penyertaan anda.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/plans') ?>" class="btn btn-secondary">Kembali</a>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <h2 class="card-title">Maklumat Pelan</h2>
                <table class="table">
                    <tbody>
                        <tr><td class="muted">Kod Pelan</td><td><?= e($plan->planCode) ?></td></tr>
                        <tr><td class="muted">Status</td><td><span class="badge badge-<?= $plan->status === 'active' ? 'success' : 'neutral' ?>"><?= e(ucfirst($plan->status)) ?></span></td></tr>
                        <tr><td class="muted">Jumlah Caruman</td><td><?= format_money($plan->contributionAmount) ?></td></tr>
                        <tr><td class="muted">Kekerapan Pembayaran</td><td><?= e($plan->paymentFrequency) ?></td></tr>
                        <tr><td class="muted">Bilangan Kitaran</td><td><?= e((string) $plan->numberOfCycles) ?></td></tr>
                        <tr><td class="muted">Mod Pembayaran</td><td><?= e($plan->payoutMode === 'fixed' ? 'Tetap' : 'Kutipan Sebenar') ?><?= $plan->payoutMode === 'fixed' ? ' (' . format_money($plan->fixedPayoutAmount) . ')' : '' ?></td></tr>
                        <tr><td class="muted">Had Ahli Maksimum</td><td><?= e((string) ($plan->maxMembers ?? '-')) ?></td></tr>
                        <tr><td class="muted">Skor Kredit Minimum</td><td><?= e((string) ($plan->minCreditScore ?? '-')) ?></td></tr>
                    </tbody>
                </table>
                <?php if (!empty($plan->description)): ?>
                    <p class="muted mt-3"><?= e($plan->description) ?></p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2 class="card-title">Kelayakan &amp; Penyertaan</h2>
                <?php if ($alreadyMember): ?>
                    <p>Anda adalah ahli pelan ini.</p>
                    <p>Status semasa:
                        <span class="badge badge-<?= $memberStatus === 'active' ? 'success' : ($memberStatus === 'pending' ? 'warning' : 'neutral') ?>">
                            <?= e(ucfirst($memberStatus ?? 'tiada')) ?>
                        </span>
                    </p>
                    <?php if ($memberStatus === 'active'): ?>
                        <a href="<?= url('/payments') ?>" class="btn btn-primary mt-3">Buat Bayaran</a>
                    <?php elseif ($memberStatus === 'pending'): ?>
                        <p class="muted small mt-3">Permintaan anda sedang disemak oleh pentadbir.</p>
                    <?php endif; ?>
                <?php elseif (($plan->minCreditScore ?? 0) > 0): ?>
                    <p class="muted">Pelan ini memerlukan skor kredit minimum
                        <strong><?= e((string) $plan->minCreditScore) ?></strong> untuk layak menyertai.</p>
                    <form method="POST" action="<?= url('/plans/' . $plan->id . '/join') ?>" style="margin-top: 1rem;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-block">Sertai Pelan</button>
                    </form>
                <?php else: ?>
                    <p class="muted">Pelan ini terbuka untuk penyertaan. Sertai sekarang untuk mula mencarum.</p>
                    <form method="POST" action="<?= url('/plans/' . $plan->id . '/join') ?>" style="margin-top: 1rem;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-block">Sertai Pelan</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php
            $systemQr = (new \App\Repositories\AppSettingRepository())->get('payment_qr_path');
            $planQr   = $plan->paymentQrPath ?? null;
            $hasQr    = ($planQr !== null && $planQr !== '') || ($systemQr !== null && $systemQr !== '');
        ?>
        <?php if ($hasQr && $alreadyMember && $memberStatus === 'active'): ?>
            <div class="card mt-4">
                <div class="card-body">
                    <div class="qr-public">
                        <?php if ($planQr): ?>
                            <img src="<?= url('/plans/' . $plan->id . '/qr') ?>" alt="QR pembayaran pelan">
                        <?php else: ?>
                            <img src="<?= url('/brand/qr') ?>" alt="QR pembayaran sistem">
                        <?php endif; ?>
                        <div class="qr-public-text">
                            <h4>QR Pembayaran <?= e($plan->name) ?></h4>
                            <p>Imbas QR ini untuk membuat bayaran, kemudian muat naik slip pada halaman Bayaran.</p>
                            <a href="<?= url('/payments') ?>" class="btn btn-primary mt-3">Muat Naik Slip Bayaran</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Jadual Giliran Pelan (Jana Jadual) -->
        <div class="card mt-4" id="jadual">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h2 class="card-title" style="margin: 0;">Jadual Kitaran &amp; Giliran Payout</h2>
                    <p class="muted small" style="margin-top: 0.25rem;">Jadual penuh setiap kitaran dan penerima wang kutu yang telah dijana untuk pelan ini.</p>
                </div>
                <?php if (!empty($isAdmin)): ?>
                    <form method="POST" action="<?= url('/admin/plans/' . $plan->id . '/generate') ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-secondary btn-sm">&#x21bb; Jana Semula Jadual</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($cycles)): ?>
                <div style="padding: 2rem; text-align: center; background: rgba(0,0,0,0.02); border-radius: 8px;">
                    <p class="muted">Jadual kitaran belum dijana untuk pelan ini.</p>
                    <?php if (!empty($isAdmin)): ?>
                        <form method="POST" action="<?= url('/admin/plans/' . $plan->id . '/generate') ?>" style="margin-top: 0.75rem;">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary btn-sm">Jana Jadual Sekarang</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kitaran</th>
                                <th>Tarikh Mula</th>
                                <th>Tarikh Tamat</th>
                                <th>Tarikh Bayaran Payout</th>
                                <th>Penerima Giliran Kutu</th>
                                <th>Jumlah Payout</th>
                                <th>Status Giliran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cycles as $c): ?>
                                <?php
                                    $pStatus = strtolower((string) ($c['payout_status'] ?? $c['status'] ?? 'scheduled'));
                                    $statusBadgeClass = match ($pStatus) {
                                        'paid' => 'badge-success',
                                        'due' => 'badge-warning',
                                        'processing' => 'badge-info',
                                        'scheduled' => 'badge-primary',
                                        'cancelled' => 'badge-danger',
                                        default => 'badge-neutral',
                                    };
                                    $statusLabel = match ($pStatus) {
                                        'paid' => 'Selesai Dibayar',
                                        'due' => 'Perlu Dibayar (Hari Ini)',
                                        'processing' => 'Sedang Diproses',
                                        'scheduled' => 'Akan Datang (Berjadual)',
                                        'cancelled' => 'Dibatalkan',
                                        default => ucfirst($pStatus),
                                    };
                                ?>
                                <tr>
                                    <td><span class="badge badge-neutral">Kitaran <?= e((string) ($c['cycle_no'] ?? '-')) ?></span></td>
                                    <td><?= e((string) ($c['start_date'] ?? '-')) ?></td>
                                    <td><?= e((string) ($c['end_date'] ?? '-')) ?></td>
                                    <td>
                                        <strong><?= e((string) ($c['payout_date'] ?? $c['start_date'] ?? '-')) ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['recipient_name'])): ?>
                                            <div style="font-weight: 600; color: #1e293b;"><?= e((string) $c['recipient_name']) ?></div>
                                            <?php if (!empty($c['recipient_code'])): ?>
                                                <span class="muted small"><?= e((string) $c['recipient_code']) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="muted">Belum Ditentukan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($c['payout_amount'])): ?>
                                            <strong style="color: #16a34a; font-size: 1rem;"><?= format_money((string) $c['payout_amount']) ?></strong>
                                        <?php else: ?>
                                            <span class="muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statusBadgeClass ?>">
                                            <?= e($statusLabel) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($mySchedules)): ?>
            <div class="card mt-4">
                <h2 class="card-title">Jadual Caruman Saya</h2>
                <p class="muted small">Senarai tarikh akhir caruman anda untuk pelan ini.</p>
                <div class="table-wrap mt-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kitaran</th>
                                <th>Tarikh Akhir (Due Date)</th>
                                <th>Jumlah Perlu Bayar</th>
                                <th>Jumlah Dibayar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mySchedules as $ms): ?>
                                <tr>
                                    <td>Kitaran <?= e((string) ($ms['cycle_no'] ?? '-')) ?></td>
                                    <td><?= e((string) ($ms['due_date'] ?? '-')) ?></td>
                                    <td><?= format_money((string) ($ms['amount'] ?? 0)) ?></td>
                                    <td><?= format_money((string) ($ms['amount_paid'] ?? 0)) ?></td>
                                    <td>
                                        <span class="badge badge-<?= ($ms['status'] ?? '') === 'paid' ? 'success' : (($ms['status'] ?? '') === 'overdue' ? 'danger' : 'warning') ?>">
                                            <?= e(ucfirst((string) ($ms['status'] ?? 'pending'))) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
