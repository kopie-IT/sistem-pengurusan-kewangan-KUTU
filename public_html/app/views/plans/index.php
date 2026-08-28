<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Penyertaan</span>
                <h1>Pelan Tersedia</h1>
                <p class="muted">Pelan yang dibuka untuk penyertaan ahli. Sertai untuk mula mencarum.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">Papan Pemuka</a>
            </div>
        </div>

        <?php if (empty($plans)): ?>
            <div class="card">
                <div class="empty-state">Tiada pelan terbuka pada masa ini.</div>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($plans as $plan): ?>
                    <div class="card">
                        <div class="flex flex-between">
                            <span class="badge badge-info"><?= e($plan->planCode) ?></span>
                            <span class="badge badge-<?= $plan->status === 'active' ? 'success' : 'neutral' ?>"><?= e(ucfirst($plan->status)) ?></span>
                        </div>
                        <h3 style="margin: 0.75rem 0 0.25rem;"><?= e($plan->name) ?></h3>
                        <p class="muted small">Caruman: <strong><?= format_money($plan->contributionAmount) ?></strong> / <?= e($plan->paymentFrequency) ?></p>

                        <ul class="small muted" style="list-style: none; padding: 0; margin: 0.75rem 0; display: grid; gap: 0.35rem;">
                            <li>Kitaran: <?= e((string) $plan->numberOfCycles) ?></li>
                            <li>Mod pembayaran: <?= e($plan->payoutMode === 'fixed' ? 'Tetap' : 'Kutipan') ?></li>
                            <li>Skor minimum: <?= e((string) $plan->minCreditScore) ?></li>
                        </ul>

                        <?php
                        $status = $memberships[$plan->id] ?? null;
                        if ($status !== null): ?>
                            <span class="badge badge-<?= $status === 'active' ? 'success' : ($status === 'pending' ? 'warning' : 'neutral') ?>">
                                <?= $status === 'active' ? 'Telah menyertai' : ($status === 'pending' ? 'Menunggu kelulusan' : ucfirst($status)) ?>
                            </span>
                        <?php else: ?>
                            <form method="POST" action="<?= url('/plans/' . $plan->id . '/join') ?>" style="margin-top: 0.75rem;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-primary btn-block">Sertai Pelan</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
