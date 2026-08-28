<?php
/** @var array $schedules */
/** @var array $planNames */
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Kalendar</span>
                <h1>Kalendar Caruman</h1>
                <p class="muted">Tarikh due caruman anda (baca sahaja).</p>
            </div>
            <div class="actions">
                <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">Papan Pemuka</a>
            </div>
        </div>

        <?php if (empty($schedules)): ?>
            <div class="card">
                <div class="empty-state">Tiada tarikh caruman dijadualkan.</div>
            </div>
        <?php elseif (isset($schedules[0]) && is_array($schedules[0])): ?>
            <?php foreach ($schedules as $groupLabel => $group): ?>
                <h2 class="card-title mt-4"><?= e(is_string($groupLabel) ? $groupLabel : 'Kumpulan') ?></h2>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tarikh Due</th>
                                <th>Kitar</th>
                                <th>Pelan</th>
                                <th>Amaun</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($group as $s): ?>
                                <tr>
                                    <td><?= e($s->due_date ?? '-') ?></td>
                                    <td><?= e((string) ($s->cycle_no ?? $s->cycleNo ?? '-')) ?></td>
                                    <td><?= e($planNames[$s->planId] ?? $s->planId ?? '-') ?></td>
                                    <td><?= format_money($s->amount ?? 0) ?></td>
                                    <td><span class="badge badge-<?= ($s->status ?? '') === 'paid' ? 'success' : (($s->status ?? '') === 'overdue' ? 'danger' : 'warning') ?>"><?= e(ucfirst($s->status ?? 'belum')) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tarikh Due</th>
                            <th>Kitar</th>
                            <th>Pelan</th>
                            <th>Amaun</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td><?= e($s->due_date ?? '-') ?></td>
                                <td><?= e((string) ($s->cycle_no ?? $s->cycleNo ?? '-')) ?></td>
                                <td><?= e($planNames[$s->planId] ?? $s->planId ?? '-') ?></td>
                                <td><?= format_money($s->amount ?? 0) ?></td>
                                <td><span class="badge badge-<?= ($s->status ?? '') === 'paid' ? 'success' : (($s->status ?? '') === 'overdue' ? 'danger' : 'warning') ?>"><?= e(ucfirst($s->status ?? 'belum')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
