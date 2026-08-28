<?php
/** @var array $payouts */
/** @var array $planNames */
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Kalendar</span>
                <h1>Kalendar Payout</h1>
                <p class="muted">Jadual pembayaran kepada ahli (baca sahaja).</p>
            </div>
            <div class="actions">
                <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">Papan Pemuka</a>
            </div>
        </div>

        <?php if (empty($payouts)): ?>
            <div class="card">
                <div class="empty-state">Tiada jadual payout dijumpai.</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tarikh Payout</th>
                            <th>Kitar</th>
                            <th>Penerima</th>
                            <th>Pelan</th>
                            <th>Kasar</th>
                            <th>Fi Admin</th>
                            <th>Bersih</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payouts as $p): ?>
                            <tr>
                                <td><?= e($p->payout_date ?? '-') ?></td>
                                <td><?= e((string) ($p->cycle_no ?? $p->cycleNo ?? '-')) ?></td>
                                <td><?= e((string) ($p->recipient_member_id ?? '-')) ?></td>
                                <td><?= e($planNames[$p->plan_id] ?? $p->plan_name ?? $p->plan_id ?? '-') ?></td>
                                <td><?= format_money($p->gross_payout ?? 0) ?></td>
                                <td><?= format_money($p->admin_fee ?? 0) ?></td>
                                <td><strong><?= format_money($p->net_payout ?? 0) ?></strong></td>
                                <td><span class="badge badge-<?= ($p->status ?? '') === 'paid' ? 'success' : (($p->status ?? '') === 'pending' ? 'warning' : 'neutral') ?>"><?= e(ucfirst($p->status ?? 'menunggu')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
