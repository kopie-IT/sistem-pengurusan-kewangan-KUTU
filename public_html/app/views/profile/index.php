<?php
/** @var object|null $user */
/** @var object|null $member */
/** @var string|null $avatarUrl */
/** @var string $avatarInitials */
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Akaun</span>
                <h1>Profil Saya</h1>
                <p class="muted">Kemaskini maklumat peribadi dan avatar anda.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/profile/change-password') ?>" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>Tukar Kata Laluan</span>
                </a>
                <a href="<?= url($isAdmin ?? false ? '/admin' : '/dashboard') ?>" class="btn btn-secondary">Papan Pemuka</a>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <h2 class="card-title">Avatar & Maklumat Akaun</h2>

                <form method="POST" action="<?= url('/profile') ?>" enctype="multipart/form-data" novalidate>
                    <?= csrf_field() ?>

                    <div class="profile-avatar-block">
                        <div class="profile-avatar-preview" data-avatar-preview>
                            <?php if (!empty($avatarUrl)): ?>
                                <img src="<?= e($avatarUrl) ?>" alt="Avatar semasa">
                            <?php else: ?>
                                <span class="user-avatar-bubble user-avatar-bubble-lg" aria-hidden="true"><?= e($avatarInitials) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="profile-avatar-controls">
                            <label for="avatar" class="form-label">Muat Naik Avatar</label>
                            <input type="file" id="avatar" name="avatar" class="form-control"
                                   accept="image/png,image/jpeg,image/webp,image/gif"
                                   data-avatar-input>
                            <p class="form-help">PNG, JPG, WebP atau GIF. Maksimum 2 MB.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="name" class="form-label">Nama Penuh</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e(old('name', $user->name ?? '')) ?>" required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Emel</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e(old('email', $user->email ?? '')) ?>" readonly>
                        <p class="form-help">Emel tidak boleh diubah.</p>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Nombor Telefon</label>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               value="<?= e(old('phone', $member->phone ?? '')) ?>"
                               placeholder="012-3456789" maxlength="20">
                    </div>

                    <?php if ($member !== null): ?>
                        <div class="form-group">
                            <label for="ic_number" class="form-label">No. Kad Pengenalan</label>
                            <input type="text" id="ic_number" name="ic_number" class="form-control"
                                   value="<?= e(old('ic_number', $member->ic_number ?? '')) ?>"
                                   placeholder="880101-01-1234" maxlength="20">
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea id="address" name="address" class="form-control" rows="3" placeholder="Alamat penuh"><?= e(old('address', $member->address ?? '')) ?></textarea>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">Simpan Profil</button>
                </form>
            </div>

            <div class="card">
                <h2 class="card-title">Maklumat Ahli</h2>
                <?php if ($member !== null): ?>
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
                            <tr><td class="muted">Telefon</td><td><?= e($member->phone ?? '-') ?></td></tr>
                            <tr><td class="muted">No. Kad Pengenalan</td><td><?= e($member->ic_number ?? '-') ?></td></tr>
                            <tr><td class="muted">Alamat</td><td><?= e($member->address ?? '-') ?></td></tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="muted">Akaun ini bukan ahli berdaftar. Maklumat peribadi disimpan pada rekod pengguna.</p>
                    <table class="table">
                        <tbody>
                            <tr><td class="muted">Nama</td><td><?= e($user->name ?? '-') ?></td></tr>
                            <tr><td class="muted">Emel</td><td><?= e($user->email ?? '-') ?></td></tr>
                            <tr><td class="muted">Telefon</td><td><?= e(old('phone', $_SESSION['user_phone'] ?? '-')) ?></td></tr>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="d-block mt-3">
                    <a href="<?= url('/profile/change-password') ?>" class="btn btn-secondary btn-block">
                        Tukar Kata Laluan
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
