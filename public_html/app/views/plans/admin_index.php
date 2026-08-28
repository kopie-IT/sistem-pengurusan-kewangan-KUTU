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
                                    <a href="<?= url('/plans/' . $plan->id) ?>" class="btn btn-secondary btn-sm">Lihat</a>
                                    <a href="<?= url('/admin/plans/' . $plan->id . '/edit') ?>" class="btn btn-ghost btn-sm">Edit</a>
                                    <form method="POST" action="<?= url('/admin/plans/' . $plan->id . '/generate') ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-sm">Jana Jadual</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
