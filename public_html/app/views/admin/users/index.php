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
                            <div class="table-actions">
                                <a href="<?= url('/admin/users/' . (int) $r['id'] . '/edit') ?>" class="btn btn-secondary btn-sm btn-icon" title="Sunting Pengguna" aria-label="Sunting Pengguna">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                                <form method="POST" action="<?= url('/admin/users/' . (int) $r['id'] . '/reset-password') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost btn-sm btn-icon"
                                            title="Tetapkan Semula Kata Laluan"
                                            aria-label="Tetapkan Semula Kata Laluan"
                                            onclick="return confirm('Tetapkan semula kata laluan untuk <?= e($r['email']) ?>?');">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    </button>
                                </form>
                                <?php if ((int) $r['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                    <form method="POST" action="<?= url('/admin/users/' . (int) $r['id'] . '/delete') ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-sm btn-danger-ghost btn-icon"
                                                title="Padam Pengguna"
                                                aria-label="Padam Pengguna"
                                                onclick="return confirm('Padam pengguna <?= e($r['email']) ?> secara kekal?');">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
