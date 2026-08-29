<?php
/** @var array $rows */
$levelBadge = static function (string $level): string {
    return match ($level) {
        'excellent' => 'success',
        'good'      => 'info',
        'fair'      => 'warning',
        'risk', 'high_risk' => 'danger',
        default     => 'neutral',
    };
};
?>
<?= flash_messages() ?>

<div class="page-header">
    <div>
        <span class="page-eyebrow">Pentadbiran</span>
        <h1>Skor Kredit Ahli</h1>
        <p class="muted">Skor dan tahap risiko kewangan setiap ahli.</p>
    </div>
</div>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Ahli</th>
                <th>Emel</th>
                <th>Skor</th>
                <th>Tahap</th>
                <th class="wrap">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="5" class="empty-state">Tiada ahli didaftarkan.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td>
                            <strong><?= e((string) $row['score']) ?></strong>
                            <span class="muted small">/ 100</span>
                        </td>
                        <td><span class="badge badge-<?= $levelBadge((string) ($row['level'] ?? 'excellent')) ?>"><?= e(ucfirst(str_replace('_', ' ', (string) ($row['level'] ?? 'excellent')))) ?></span></td>
                        <td class="wrap">
                            <div class="table-actions">
                                <a href="<?= url('/admin/credit-scores/' . (int) $row['member_id']) ?>" class="btn btn-secondary btn-sm btn-icon" title="Lihat Sejarah Skor Kredit" aria-label="Lihat Sejarah Skor Kredit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
