<?php
/** @var array $members */
/** @var string|null $search */
/** @var int $page */
$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'active'   => 'success',
        'pending'  => 'warning',
        'suspended'=> 'danger',
        'inactive' => 'neutral',
        default => 'neutral',
    };
};
?>
<?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1>Urus Ahli</h1>
                <p class="muted">Senarai semua ahli berdaftar.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/members/create') ?>" class="btn btn-primary">+ Cipta Ahli</a>
            </div>
        </div>

        <form method="GET" action="<?= url('/admin/members') ?>" class="toolbar">
            <input type="text" name="search" class="form-control search" placeholder="Cari nama, emel atau kod ahli..." value="<?= e($search ?? '') ?>">
            <button type="submit" class="btn btn-secondary">Cari</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Nama</th>
                        <th>Emel</th>
                        <th>Telefon</th>
                        <th>Skor</th>
                        <th>Status</th>
                        <th class="wrap">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr><td colspan="7" class="empty-state">Tiada ahli dijumpai.</td></tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td><?= e($m->member_code ?? '-') ?></td>
                                <td><?= e($m->name ?? '-') ?></td>
                                <td><?= e($m->email ?? '-') ?></td>
                                <td><?= e($m->phone ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($m->credit_score)): ?>
                                        <span class="badge badge-<?= ($m->credit_score ?? 0) < 40 ? 'danger' : (($m->credit_score ?? 0) < 70 ? 'warning' : 'success') ?>"><?= e((string) $m->credit_score) ?></span>
                                    <?php else: ?>
                                        <span class="muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?= $statusBadge($m->status ?? null) ?>"><?= e(ucfirst($m->status ?? 'tiada')) ?></span></td>
                                <td class="wrap">
                                    <a href="<?= url('/admin/members/' . $m->id) ?>" class="btn btn-secondary btn-sm">Lihat</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($members) && ($page ?? 1) > 1): ?>
            <a href="<?= url('/admin/members?page=' . ($page - 1)) ?>" class="btn btn-ghost mt-3">Sebelum</a>
        <?php endif; ?>
