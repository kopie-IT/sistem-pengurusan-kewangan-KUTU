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
    </div>
</section>
