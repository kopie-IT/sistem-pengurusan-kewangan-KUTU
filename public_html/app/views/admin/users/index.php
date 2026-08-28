<?php
/**
 * @var array $rows
 * @var string $search
 */
$search = $search ?? '';
$rows   = $rows ?? [];

$roleTone = static fn (string $slug): string => match ($slug) {
    'super_admin' => 'brand',
    'admin'       => 'cyan',
    'staff'       => 'neutral',
    default       => 'neutral',
};

$statusTone = static fn (string $st): string => match ($st) {
    'active'    => 'success',
    'inactive'  => 'neutral',
    'suspended' => 'danger',
    default     => 'neutral',
};
?>
<?= flash_messages() ?>

<section class="page-header">
    <div>
        <span class="page-eyebrow">Pentadbiran · Sistem</span>
        <h1>Urus Pengguna Dalaman</h1>
        <p class="muted">Akaun admin, super admin, dan staf yang boleh akses kawasan pentadbiran.</p>
    </div>
    <div class="actions">
        <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary">+ Cipta Pengguna</a>
    </div>
</section>

<form method="GET" action="<?= url('/admin/users') ?>" class="toolbar">
    <input type="text" name="search" class="form-control search"
           placeholder="Cari nama atau emel..."
           value="<?= e($search) ?>">
    <button type="submit" class="btn btn-secondary">Cari</button>
    <?php if ($search !== ''): ?>
        <a href="<?= url('/admin/users') ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
</form>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Emel</th>
                <th>Peranan</th>
                <th>Status</th>
                <th>Log masuk terakhir</th>
                <th class="wrap">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="empty-state">Tiada pengguna dijumpai.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <strong><?= e($r['name']) ?></strong>
                            <?php if ((int) ($r['must_reset_password'] ?? 0) === 1): ?>
                                <span class="badge badge-warning" title="Pengguna perlu tetapkan semula kata laluan">Reset</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($r['email']) ?></td>
                        <td><span class="badge badge-<?= $roleTone($r['role_slug']) ?>"><?= e($r['role_name']) ?></span></td>
                        <td><span class="badge badge-<?= $statusTone($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                        <td><?= e($r['last_login_at'] ? date('d M Y H:i', strtotime((string) $r['last_login_at'])) : '—') ?></td>
                        <td class="wrap">
                            <a href="<?= url('/admin/users/' . (int) $r['id'] . '/edit') ?>" class="btn btn-secondary btn-sm">Sunting</a>
                            <form method="POST" action="<?= url('/admin/users/' . (int) $r['id'] . '/reset-password') ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-ghost btn-sm"
                                        onclick="return confirm('Tetapkan semula kata laluan untuk <?= e($r['email']) ?>?');">
                                    Reset Kata Laluan
                                </button>
                            </form>
                            <?php if ((int) $r['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                <form method="POST" action="<?= url('/admin/users/' . (int) $r['id'] . '/delete') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost btn-sm btn-danger-ghost"
                                            onclick="return confirm('Padam pengguna <?= e($r['email']) ?> secara kekal?');">
                                        Padam
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
