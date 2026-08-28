<?php
/** @var object $member */
/** @var array $plans */
/** @var array|null $score */
/** @var array $history */
$statusBadge = function (?string $st): string {
    return match ($st ?? '') {
        'active'   => 'success',
        'pending'  => 'warning',
        'suspended'=> 'danger',
        default => 'neutral',
    };
};
?>
<?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Pentadbiran</span>
                <h1><?= e($member->name ?? 'Ahli') ?></h1>
                <p class="muted">Kod: <?= e($member->member_code ?? '-') ?> · <?= e($member->email ?? '') ?></p>
            </div>
            <div class="actions">
                <a href="<?= url('/admin/members') ?>" class="btn btn-secondary">Kembali</a>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <h2 class="card-title">Profil Ahli</h2>
                <table class="table">
                    <tbody>
                        <tr><td class="muted">Nama</td><td><?= e($member->name ?? '-') ?></td></tr>
                        <tr><td class="muted">Emel</td><td><?= e($member->email ?? '-') ?></td></tr>
                        <tr><td class="muted">Telefon</td><td><?= e($member->phone ?? '-') ?></td></tr>
                        <tr><td class="muted">No. KP</td><td><?= e($member->ic_number ?? '-') ?></td></tr>
                        <tr><td class="muted">Alamat</td><td><?= e($member->address ?? '-') ?></td></tr>
                        <tr><td class="muted">Skor Kredit</td><td>
                            <?php if (!empty($member->credit_score)): ?>
                                <span class="badge badge-info"><?= e((string) $member->credit_score) ?></span>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td></tr>
                        <tr><td class="muted">Status</td><td><span class="badge badge-<?= $statusBadge($member->status ?? null) ?>"><?= e(ucfirst($member->status ?? 'tiada')) ?></span></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2 class="card-title">Skor Kredit &amp; Sejarah</h2>
                <?php if (!empty($score)): ?>
                    <p>Tahap: <span class="badge badge-<?= ($score['level'] ?? '') === 'Excellent' ? 'success' : (($score['level'] ?? '') === 'High Risk' ? 'danger' : 'warning') ?>"><?= e($score['level'] ?? '-') ?></span></p>
                    <p class="muted">Skor semasa: <strong><?= e((string) ($score['score'] ?? 0)) ?></strong> / 100</p>
                <?php else: ?>
                    <p class="muted">Tiada skor dijana.</p>
                <?php endif; ?>

                <?php if (!empty($history)): ?>
                    <div class="table-wrap mt-3">
                        <table class="table">
                            <thead>
                                <tr><th>Tarikh</th><th>Acara</th><th>Perubahan</th><th>Baru</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($history, 0, 10) as $h): ?>
                                    <tr>
                                        <td><?= e($h->created_at ?? '-') ?></td>
                                        <td><?= e($h->event ?? '-') ?></td>
                                        <td><?= e((string) ($h->score_change ?? 0)) ?></td>
                                        <td><?= e((string) ($h->new_score ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="card-title mt-5">Pelan Disertai</h2>
        <?php if (empty($plans)): ?>
            <div class="card">
                <div class="empty-state">Ahli ini belum menyertai sebarang pelan.</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pelan</th>
                            <th>Status</th>
                            <th>Tarikh Sertai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $pm): ?>
                            <tr>
                                <td><?= e($pm->plan_name ?? $pm->planName ?? '-') ?></td>
                                <td><span class="badge badge-<?= $statusBadge($pm->status ?? null) ?>"><?= e(ucfirst($pm->status ?? 'tiada')) ?></span></td>
                                <td><?= e($pm->joined_at ?? $pm->joinedAt ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
