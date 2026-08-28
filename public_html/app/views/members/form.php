<?php
/** @var object|null $member */
/** @var string $title */
$isEdit = !empty($member);
$action = $isEdit ? url('/admin/members/' . $member->id) : url('/admin/members');
$val = fn(string $k) => old($k, $member->$k ?? '');
?>
<?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1><?= e($title) ?></h1>
                <p class="muted"><?= $isEdit ? 'Kemaskini maklumat ahli.' : 'Cipta akaun ahli baharu (kata laluan sementara dijana secara automatik).' ?></p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/members') ?>" class="btn btn-secondary">Kembali</a>
            </div>
        </div>

        <div class="card">
            <form method="POST" action="<?= $action ?>" novalidate>
                <?= csrf_field() ?>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="name" class="form-label">Nama</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e($val('name')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Emel</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e($val('email')) ?>" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="phone" class="form-label">Telefon</label>
                        <input type="text" id="phone" name="phone" class="form-control"
                               value="<?= e($val('phone')) ?>" placeholder="012-3456789">
                    </div>

                    <div class="form-group">
                        <label for="ic_number" class="form-label">No. Kad Pengenalan</label>
                        <input type="text" id="ic_number" name="ic_number" class="form-control"
                               value="<?= e($val('ic_number')) ?>" placeholder="880101-01-1234">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea id="address" name="address" class="form-control" rows="3" placeholder="Alamat penuh"><?= e($val('address')) ?></textarea>
                </div>

                <div class="flex flex-wrap mt-4" style="gap: 0.75rem;">
                    <button type="submit" class="btn btn-primary btn-lg">Simpan Ahli</button>
                    <a href="<?= url('/admin/members') ?>" class="btn btn-ghost btn-lg">Batal</a>
                </div>
            </form>
        </div>
