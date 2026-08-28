<?php
/** @var object|null $user */
/** @var object|null $member */
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Akaun</span>
                <h1>Profil Saya</h1>
                <p class="muted">Kemaskini maklumat peribadi anda.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">Papan Pemuka</a>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <h2 class="card-title">Maklumat Ahli</h2>
                <table class="table">
                    <tbody>
                        <tr><td class="muted">Kod Ahli</td><td><?= e($member->member_code ?? '-') ?></td></tr>
                        <tr><td class="muted">Skor Kredit</td><td>
                            <?php if (!empty($member->credit_score)): ?>
                                <span class="badge badge-info"><?= e((string) $member->credit_score) ?> / 100</span>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td></tr>
                        <tr><td class="muted">No. Kad Pengenalan</td><td><?= e($member->ic_number ?? '-') ?></td></tr>
                        <tr><td class="muted">Telefon</td><td><?= e($member->phone ?? '-') ?></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2 class="card-title">Kemaskini Profil</h2>
                <form method="POST" action="<?= url('/profile') ?>" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="name" class="form-label">Nama</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e(old('name', $user->name ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Emel</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e(old('email', $user->email ?? '')) ?>" readonly>
                        <p class="form-help">Emel tidak boleh diubah.</p>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Telefon</label>
                        <input type="text" id="phone" name="phone" class="form-control"
                               value="<?= e(old('phone', $member->phone ?? '')) ?>" placeholder="012-3456789">
                    </div>

                    <div class="form-group">
                        <label for="ic_number" class="form-label">No. Kad Pengenalan</label>
                        <input type="text" id="ic_number" name="ic_number" class="form-control"
                               value="<?= e(old('ic_number', $member->ic_number ?? '')) ?>" placeholder="880101-01-1234">
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea id="address" name="address" class="form-control" rows="3" placeholder="Alamat penuh"><?= e(old('address', $member->address ?? '')) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">Simpan Profil</button>
                </form>
            </div>
        </div>
    </div>
</section>
