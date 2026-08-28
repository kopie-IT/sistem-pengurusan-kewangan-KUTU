<?php
/**
 * Shared sidebar navigation (hierarchical).
 *
 * Rendered inside layouts/main.php when the user is authenticated.
 * The menu structure is grouped into sections with parent links and
 * indented child links so the hierarchy is clear and scannable.
 */

declare(strict_types=1);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$role = $_SESSION['user_role'] ?? '';

$isActive = static function (string $path, bool $exact = false) use ($currentPath): bool {
    if ($exact) {
        return $currentPath === $path;
    }
    if ($path === '/') {
        return $currentPath === '/';
    }
    return $currentPath === $path || str_starts_with($currentPath, rtrim($path, '/') . '/');
};

$isAdmin = in_array($role, ['admin', 'super_admin'], true);

if ($isAdmin) {
    $sideTitle = 'Pentadbir';
    $groups = [
        [
            'label' => null,
            'items' => [
                ['label' => 'Laman Utama', 'url' => '/admin'],
            ],
        ],
        [
            'label' => 'Pengurusan',
            'items' => [
                ['label' => 'Pelan', 'url' => '/admin/plans'],
                ['label' => 'Ahli', 'url' => '/admin/members'],
            ],
        ],
        [
            'label' => 'Kewangan',
            'items' => [
                ['label' => 'Pembayaran', 'url' => '/admin/payments'],
                ['label' => 'Payout', 'url' => '/admin/payouts'],
                ['label' => 'Kekurangan', 'url' => '/admin/shortfalls'],
                ['label' => 'Pengeluaran', 'url' => '/admin/withdrawals'],
            ],
        ],
        [
            'label' => 'Laporan',
            'items' => [
                ['label' => 'Papan Pemuka', 'url' => '/admin/reports'],
                ['label' => 'Kewangan', 'url' => '/admin/reports/financial'],
                ['label' => 'Pelan', 'url' => '/admin/reports/plans'],
                ['label' => 'Ahli', 'url' => '/admin/reports/members'],
            ],
        ],
        [
            'label' => null,
            'items' => [
                ['label' => 'Skor Kredit', 'url' => '/admin/credit-scores'],
            ],
        ],
    ];
} else {
    $sideTitle = 'Ahli';
    $groups = [
        [
            'label' => null,
            'items' => [
                ['label' => 'Papan Pemuka', 'url' => '/dashboard'],
            ],
        ],
        [
            'label' => 'Pelan',
            'items' => [
                ['label' => 'Semua Pelan', 'url' => '/plans'],
            ],
        ],
        [
            'label' => 'Bayaran',
            'items' => [
                ['label' => 'Bayaran Saya', 'url' => '/payments'],
                ['label' => 'Bayaran Pukal', 'url' => '/payments/bulk'],
            ],
        ],
        [
            'label' => 'Payout',
            'items' => [
                ['label' => 'Payout Saya', 'url' => '/payouts/me'],
            ],
        ],
        [
            'label' => 'Kalendar',
            'items' => [
                ['label' => 'Caruman', 'url' => '/calendar/contribution'],
                ['label' => 'Payout', 'url' => '/calendar/payout'],
            ],
        ],
        [
            'label' => 'Akaun',
            'items' => [
                ['label' => 'Skor Kredit', 'url' => '/credit-score'],
                ['label' => 'Pengeluaran', 'url' => '/withdrawals'],
                ['label' => 'Profil', 'url' => '/profile'],
                ['label' => 'Makluman', 'url' => '/notifications'],
            ],
        ],
    ];
}
?>
<aside class="app-sidebar">
    <div class="side-title"><?= e($sideTitle) ?></div>
    <nav class="app-nav" aria-label="Navigasi <?= e($sideTitle) ?>">
        <?php foreach ($groups as $group): ?>
            <?php if ($group['label'] !== null): ?>
                <div class="app-nav-group">
                    <div class="app-nav-label"><?= e($group['label']) ?></div>
                    <div class="app-nav-children">
                        <?php foreach ($group['items'] as $item): ?>
                            <a href="<?= e(url($item['url'])) ?>"
                               class="<?= $isActive($item['url']) ? 'is-active' : '' ?>"><?= e($item['label']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($group['items'] as $item): ?>
                    <a href="<?= e(url($item['url'])) ?>"
                       class="<?= $isActive($item['url']) ? 'is-active' : '' ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
