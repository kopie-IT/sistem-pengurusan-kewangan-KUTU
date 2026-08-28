<?php
/** @var array $notifications */
$hasUnread = false;
foreach ($notifications ?? [] as $n) {
    if (!empty($n->is_read)) continue;
    $hasUnread = true;
    break;
}
?>
<section class="section" style="padding-top: 2.5rem;">
    <div class="container">
        <?= flash_messages() ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">Makluman</span>
                <h1>Pemberitahuan</h1>
                <p class="muted">Semua pemberitahuan dan makluman sistem anda.</p>
            </div>
            <div class="actions">
                <?php if ($hasUnread): ?>
                    <form method="POST" action="<?= url('/notifications/read-all') ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-secondary">Tanda semua dibaca</button>
                    </form>
                <?php endif; ?>
                <a href="<?= url('/dashboard') ?>" class="btn btn-ghost">Papan Pemuka</a>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="card">
                <div class="empty-state">Tiada pemberitahuan buat masa ini.</div>
            </div>
        <?php else: ?>
            <div class="flex flex-col" style="gap: 1rem;">
                <?php foreach ($notifications as $n): ?>
                    <?php $unread = empty($n->is_read); ?>
                    <div class="card" style="<?= $unread ? 'border-left: 3px solid var(--color-primary);' : '' ?>">
                        <div class="flex flex-between">
                            <div>
                                <span class="badge badge-<?= e($n->type === 'alert' ? 'danger' : ($n->type === 'warning' ? 'warning' : 'info')) ?>">
                                    <?= e(ucfirst($n->type ?? 'info')) ?>
                                </span>
                                <strong style="margin-left: 0.5rem;"><?= e($n->title ?? '') ?></strong>
                                <?php if ($unread): ?><span class="badge badge-warning small" style="margin-left:0.5rem;">Baru</span><?php endif; ?>
                            </div>
                            <span class="muted small"><?= e($n->created_at ?? '') ?></span>
                        </div>
                        <p class="muted mt-2"><?= e($n->message ?? '') ?></p>
                        <?php if ($unread): ?>
                            <form method="POST" action="<?= url('/notifications/' . $n->id . '/read') ?>" style="margin-top: 1rem;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-ghost btn-sm">Tanda dibaca</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
