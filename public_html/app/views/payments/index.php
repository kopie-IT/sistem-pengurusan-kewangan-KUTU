<?php
/** @var array $schedules */
/** @var array $payments */
/** @var array $planNames */
$outstanding = array_filter($schedules ?? [], fn($s) => ($s->status ?? '') !== 'paid');
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Kewangan</span>
                <h1>Bayaran Saya</h1>
                <p class="muted">Caruman tertunggak dan sejarah bayaran anda.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">Papan Pemuka</a>
            </div>
        </div>

        <h2 class="card-title mt-4">Caruman Tertunggak</h2>
        <?php if (empty($outstanding)): ?>
            <div class="card">
                <div class="empty-state">Tiada caruman tertunggak. Tahniah!</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pelan</th>
                            <th>Tarikh Due</th>
                            <th>Jumlah</th>
                            <th>Dibayar</th>
                            <th>Baki</th>
                            <th>Status</th>
                            <th class="wrap">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outstanding as $s): ?>
                            <?php $balance = ($s->amount ?? 0) - ($s->amount_paid ?? 0); ?>
                            <tr>
                                <td><?= e($planNames[$s->planId] ?? $s->planId ?? '-') ?></td>
                                <td><?= e($s->due_date ?? '-') ?></td>
                                <td><?= format_money($s->amount ?? 0) ?></td>
                                <td><?= format_money($s->amount_paid ?? 0) ?></td>
                                <td><strong><?= format_money($balance) ?></strong></td>
                                <td><span class="badge badge-<?= ($s->status ?? '') === 'overdue' ? 'danger' : 'warning' ?>"><?= e(ucfirst($s->status ?? 'belum')) ?></span></td>
                                <td class="wrap">
                                    <div class="table-actions">
                                        <a href="<?= url('/payments/single/' . $s->id) ?>" class="btn btn-primary btn-sm btn-icon" title="Buat Bayaran" aria-label="Buat Bayaran">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($outstanding) > 1): ?>
                <a href="<?= url('/payments/bulk') ?>" class="btn btn-secondary mt-3">Buat Bayaran Pukal</a>
            <?php endif; ?>
        <?php endif; ?>

        <h2 class="card-title mt-5">Sejarah Bayaran</h2>
        <?php if (empty($payments)): ?>
            <div class="card">
                <div class="empty-state">Tiada rekod bayaran lagi.</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelan</th>
                            <th>Jumlah</th>
                            <th>Tarikh</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= e((string) $p->id) ?></td>
                                <td><?= e($planNames[$p->plan_id] ?? $p->plan_id ?? '-') ?></td>
                                <td><?= format_money($p->amount ?? 0) ?></td>
                                <td><?= e($p->created_at ?? '-') ?></td>
                                <td><span class="badge badge-<?= ($p->status ?? '') === 'verified' || ($p->status ?? '') === 'paid' ? 'success' : (($p->status ?? '') === 'rejected' ? 'danger' : 'warning') ?>"><?= e(ucfirst($p->status ?? 'menunggu')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
