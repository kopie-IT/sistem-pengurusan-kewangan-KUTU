<?php
/**
 * Shared sidebar navigation (hierarchical).
 *
 * Rendered inside layouts/main.php when the user is authenticated.
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

$icon = static function (string $key): string {
    $paths = [
        'dashboard' => '<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
        'plans'     => '<path d="M4 6h16M4 12h16M4 18h10"/><circle cx="18" cy="18" r="2"/>',
        'members'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'payments'  => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'payouts'   => '<path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'shortfalls'=> '<path d="M12 9v4"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 17h.01"/>',
        'withdrawals'=> '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'financial' => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'report'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M8 9h2"/>',
        'credit'    => '<path d="M12 2 4 5v6c0 5 3.41 9.74 8 11 4.59-1.26 8-6 8-11V5l-8-3z"/>',
        'score'     => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>',
    ];
    $d = $paths[$key] ?? $paths['dashboard'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
};

$isAdmin = in_array($role, ['admin', 'super_admin', 'staff'], true);

if ($isAdmin) {
    $sideTitle = 'Pentadbir';
    $groups = [
        [
            'label' => null,
            'items' => [
                ['label' => 'Dashboard', 'url' => '/admin', 'icon' => 'dashboard'],
            ],
        ],
        [
            'label' => 'Pengurusan',
            'items' => [
                ['label' => 'Pelan', 'url' => '/admin/plans', 'icon' => 'plans'],
                ['label' => 'Ahli', 'url' => '/admin/members', 'icon' => 'members'],
            ],
        ],
        [
            'label' => 'Kewangan',
            'items' => [
                ['label' => 'Pembayaran', 'url' => '/admin/payments', 'icon' => 'payments'],
                ['label' => 'Payout', 'url' => '/admin/payouts', 'icon' => 'payouts'],
                ['label' => 'Kekurangan', 'url' => '/admin/shortfalls', 'icon' => 'shortfalls'],
                ['label' => 'Pengeluaran', 'url' => '/admin/withdrawals', 'icon' => 'withdrawals'],
            ],
        ],
        [
            'label' => 'Laporan',
            'items' => [
                ['label' => 'Kewangan', 'url' => '/admin/reports/financial', 'icon' => 'financial'],
                ['label' => 'Pelan', 'url' => '/admin/reports/plans', 'icon' => 'report'],
                ['label' => 'Ahli', 'url' => '/admin/reports/members', 'icon' => 'members'],
            ],
        ],
        [
            'label' => null,
            'items' => [
                ['label' => 'Skor Kredit', 'url' => '/admin/credit-scores', 'icon' => 'credit'],
            ],
        ],
    ];
} else {
    $sideTitle = 'Ahli';
    $groups = [
        [
            'label' => null,
            'items' => [
                ['label' => 'Papan Pemuka', 'url' => '/dashboard', 'icon' => 'dashboard'],
            ],
        ],
        [
            'label' => 'Pelan',
            'items' => [
                ['label' => 'Semua Pelan', 'url' => '/plans', 'icon' => 'plans'],
            ],
        ],
        [
            'label' => 'Bayaran',
            'items' => [
                ['label' => 'Bayaran Saya', 'url' => '/payments', 'icon' => 'payments'],
                ['label' => 'Bayaran Pukal', 'url' => '/payments/bulk', 'icon' => 'payments'],
            ],
        ],
        [
            'label' => 'Payout',
            'items' => [
                ['label' => 'Payout Saya', 'url' => '/payouts/me', 'icon' => 'payouts'],
            ],
        ],
        [
            'label' => 'Kalendar',
            'items' => [
                ['label' => 'Caruman', 'url' => '/calendar/contribution', 'icon' => 'dashboard'],
                ['label' => 'Payout', 'url' => '/calendar/payout', 'icon' => 'payouts'],
            ],
        ],
        [
            'label' => 'Akaun',
            'items' => [
                ['label' => 'Skor Kredit', 'url' => '/credit-score', 'icon' => 'credit'],
                ['label' => 'Pengeluaran', 'url' => '/withdrawals', 'icon' => 'withdrawals'],
                ['label' => 'Profil', 'url' => '/profile', 'icon' => 'members'],
                ['label' => 'Makluman', 'url' => '/notifications', 'icon' => 'score'],
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
                               class="<?= $isActive($item['url']) ? 'is-active' : '' ?>">
                                <span class="app-nav-icon" aria-hidden="true"><?= $icon($item['icon'] ?? 'dashboard') ?></span>
                                <span><?= e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($group['items'] as $item): ?>
                    <a href="<?= e(url($item['url'])) ?>"
                       class="<?= $isActive($item['url']) ? 'is-active' : '' ?>">
                        <span class="app-nav-icon" aria-hidden="true"><?= $icon($item['icon'] ?? 'dashboard') ?></span>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
