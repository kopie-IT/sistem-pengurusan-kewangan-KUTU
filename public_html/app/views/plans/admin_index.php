<?php
/** @var array $plans */
/** @var string|null $status */
/** @var string|null $search */
?>
<?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Urus Pelan</h1>
                <p class="muted">Senarai semua pelan simpanan Main Kutu.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/plans/create') ?>" class="btn btn-primary">+ Cipta Pelan</a>
            </div>
        </div>

        <form method="GET" action="<?= url('/admin/plans') ?>" class="toolbar">
            <input type="text" name="search" class="form-control search" placeholder="Cari kod atau nama pelan..." value="<?= e($search ?? '') ?>">
            <select name="status" class="form-control" style="max-width: 180px;">
                <option value="">Semua status</option>
                <?php foreach (['draft', 'open', 'full', 'active', 'suspended', 'completed', 'cancelled'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Cari</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Nama</th>
                        <th>Caruman</th>
                        <th>Kitaran</th>
                        <th>Ahli</th>
                        <th>Status</th>
                        <th class="wrap">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plans)): ?>
                        <tr><td colspan="7" class="empty-state">Tiada pelan dijumpai.</td></tr>
                    <?php else: ?>
                        <?php foreach ($plans as $plan): ?>
                            <tr>
                                <td><?= e($plan->planCode) ?></td>
                                <td><?= e($plan->name) ?></td>
                                <td><?= format_money($plan->contributionAmount) ?></td>
                                <td><?= e((string) $plan->numberOfCycles) ?></td>
                                <td><?= e((string) ($plan->maxMembers ?? '-')) ?></td>
                                <td><span class="badge badge-<?= $plan->status === 'active' ? 'success' : 'neutral' ?>"><?= e(ucfirst($plan->status)) ?></span></td>
                                <td class="wrap">
                                    <div class="table-actions">
                                        <a href="<?= url('/plans/' . $plan->id) ?>" class="btn btn-secondary btn-sm btn-icon" title="Lihat Pelan" aria-label="Lihat Pelan">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <a href="<?= url('/plans/' . $plan->id . '#jadual') ?>" class="btn btn-ghost btn-sm btn-icon" title="Lihat Jadual" aria-label="Lihat Jadual">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </a>
                                        <a href="<?= url('/admin/plans/' . $plan->id . '/edit') ?>" class="btn btn-ghost btn-sm btn-icon" title="Sunting Pelan" aria-label="Sunting Pelan">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </a>
                                        <form method="POST" action="<?= url('/admin/plans/' . $plan->id . '/generate') ?>" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-primary btn-sm btn-icon" title="Jana Jadual" aria-label="Jana Jadual">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
