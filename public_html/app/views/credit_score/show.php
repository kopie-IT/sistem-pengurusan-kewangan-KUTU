<?php
/** @var array|null $score */
/** @var array $history */
$levelMap = [
    'Excellent' => ['label' => 'Cemerlang', 'badge' => 'success'],
    'Good'      => ['label' => 'Baik', 'badge' => 'info'],
    'Fair'      => ['label' => 'Sederhana', 'badge' => 'warning'],
    'Risk'      => ['label' => 'Risiko', 'badge' => 'warning'],
    'High Risk' => ['label' => 'Risiko Tinggi', 'badge' => 'danger'],
];
$level = $levelMap[$score['level'] ?? ''] ?? ['label' => 'Tiada', 'badge' => 'neutral'];
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Profil</span>
                <h1>Skor Kredit Saya</h1>
                <p class="muted">Skor kebolehpercayaan dan sejarah kemas kini.</p>
            </div>
            <div class="actions">
                <a href="<?= url('/dashboard') ?>" class="btn btn-secondary">Papan Pemuka</a>
            </div>
        </div>

        <?php if (empty($score)): ?>
            <div class="card">
                <div class="empty-state">Tiada skor kredit dijana lagi.</div>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <div class="card" style="text-align: center;">
                    <h2 class="card-title">Skor Semasa</h2>
                    <div class="flex" style="justify-content: center; align-items: center; gap: 1rem; margin: 1rem 0;">
                        <span class="badge badge-<?= $level['badge'] ?>" style="font-size: 1.5rem; padding: 1rem 1.5rem; border-radius: 999px;">
                            <?= e((string) ($score['score'] ?? 0)) ?>
                        </span>
                    </div>
                    <p class="muted">Tahap: <strong><?= e($level['label']) ?></strong></p>
                </div>

                <div class="card">
                    <h2 class="card-title">Ringkasan</h2>
                    <table class="table">
                        <tbody>
                            <tr><td class="muted">Skor</td><td><?= e((string) ($score['score'] ?? 0)) ?> / 100</td></tr>
                            <tr><td class="muted">Tahap</td><td><span class="badge badge-<?= $level['badge'] ?>"><?= e($level['label']) ?></span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <h2 class="card-title mt-5">Sejarah Skor</h2>
            <?php if (empty($history)): ?>
                <div class="card">
                    <div class="empty-state">Tiada rekod sejarah.</div>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tarikh</th>
                                <th>Acara</th>
                                <th>Kod</th>
                                <th>Skor Sebelum</th>
                                <th>Perubahan</th>
                                <th>Skor Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                                <tr>
                                    <td><?= e($h->created_at ?? '-') ?></td>
                                    <td><?= e($h->event ?? '-') ?></td>
                                    <td><?= e($h->reason_code ?? '-') ?></td>
                                    <td><?= e((string) ($h->previous_score ?? '-')) ?></td>
                                    <td>
                                        <span class="badge badge-<?= ($h->score_change ?? 0) >= 0 ? 'success' : 'danger' ?>">
                                            <?= ($h->score_change ?? 0) >= 0 ? '+' : '' ?><?= e((string) ($h->score_change ?? 0)) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= e((string) ($h->new_score ?? '-')) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
