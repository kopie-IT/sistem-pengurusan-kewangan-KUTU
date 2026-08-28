<?php
/**
 * @var string $mode  'create'|'edit'
 * @var ?array $user
 * @var string $role
 * @var string $status
 */
$mode    = $mode ?? 'create';
$user    = $user ?? null;
$role    = $role ?? 'staff';
$status  = $status ?? 'active';

$action = $mode === 'create'
    ? url('/admin/users')
    : url('/admin/users/' . (int) ($user['id'] ?? 0));

$pageTitle = $mode === 'create' ? 'Cipta Pengguna Dalaman' : 'Sunting Pengguna';
?>
<?= flash_messages() ?>

<section class="page-header">
    <div>
        <span class="page-eyebrow">Pentadbiran · Sistem · Pengguna</span>
        <h1><?= e($pageTitle) ?></h1>
        <p class="muted">Akaun dalaman sistem (admin / super admin / staf). Ahli biasa diurus di menu Ahli.</p>
    </div>
    <div class="actions">
        <a href="<?= url('/admin/users') ?>" class="btn btn-ghost">← Kembali</a>
    </div>
</section>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= e($action) ?>" class="form-grid" novalidate>
            <?= csrf_field() ?>

            <div class="form-group form-grid form-grid-2">
                <div>
                    <label for="name" class="form-label">Nama penuh</label>
                    <input type="text" id="name" name="name" required maxlength="100"
                           value="<?= e($user['name'] ?? old('name')) ?>" class="form-control">
                </div>
                <div>
                    <label for="email" class="form-label">Emel</label>
                    <input type="email" id="email" name="email" required maxlength="150"
                           value="<?= e($user['email'] ?? old('email')) ?>" class="form-control"
                           autocomplete="off">
                </div>
            </div>

            <div class="form-group form-grid form-grid-2">
                <div>
                    <label for="role" class="form-label">Peranan</label>
                    <select id="role" name="role" class="form-control">
                        <option value="super_admin" <?= $role === 'super_admin' ? 'selected' : '' ?>>Super Admin (akses penuh + tetapan)</option>
                        <option value="admin"       <?= $role === 'admin'       ? 'selected' : '' ?>>Admin (akses pentadbiran)</option>
                        <option value="staff"       <?= $role === 'staff'       ? 'selected' : '' ?>>Staf (admin tanpa tetapan)</option>
                    </select>
                    <p class="form-help">Super admin boleh akses Tetapan Sistem (logo, QR, email blast, wap.net). Admin tidak boleh. Staf sama seperti admin tetapi diketepikan daripada Tetapan.</p>
                </div>
                <div>
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active"     <?= $status === 'active'     ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive"   <?= $status === 'inactive'   ? 'selected' : '' ?>>Tidak aktif</option>
                        <option value="suspended"  <?= $status === 'suspended'  ? 'selected' : '' ?>>Digantung</option>
                    </select>
                </div>
            </div>

            <?php if ($mode === 'create'): ?>
                <fieldset class="form-group">
                    <legend class="form-label">Kata laluan</legend>
                    <p class="form-help">Kosongkan untuk menjana kata laluan sementara secara rawak. Pilihan minimum 8 aksara.</p>
                    <input type="text" name="password" minlength="8" maxlength="128" class="form-control"
                           placeholder="Ataupun biarkan kosong untuk dijana automatik" autocomplete="new-password">
                    <label class="checkbox-row" style="margin-top: var(--space-3);">
                        <input type="checkbox" name="force_reset" value="1" checked>
                        <span>Paksa pengguna menetapkan semula kata laluan selepas log masuk pertama</span>
                    </label>
                </fieldset>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $mode === 'create' ? 'Cipta Pengguna' : 'Simpan Perubahan' ?>
                </button>
                <a href="<?= url('/admin/users') ?>" class="btn btn-ghost">Batal</a>
            </div>
        </form>
    </div>
</div>
