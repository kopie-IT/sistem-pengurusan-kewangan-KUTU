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
    // Exact match always wins over any prefix logic.
    if ($currentPath === $path) {
        return true;
    }
    // For child paths: only treat as active when the current URL is a true
    // descendant (next segment exists), so a parent menu like /admin doesn't
    // stay highlighted when navigating to /admin/plans.
    $path = rtrim($path, '/');
    if (!str_starts_with($currentPath, $path . '/')) {
        return false;
    }
    // Disallow "logical parent" matches (e.g. /admin -> /admin/plans only,
    // not /admin -> /administration). Done by ensuring the character that
    // follows the path is a directory separator (handled above).
    return true;
};

$icon = static function (string $key): string {
    $paths = [
        'dashboard' => '<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
        'plans'     => '<path d="M4 6h16M4 12h16M4 18h10"/><circle cx="18" cy="18" r="2"/>',
        'members'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'users'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0 1 16 0v1"/>',
        'payments'  => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'payouts'   => '<path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'shortfalls'=> '<path d="M12 9v4"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 17h.01"/>',
        'withdrawals'=> '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'financial' => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'report'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M8 9h2"/>',
        'credit'    => '<path d="M12 2 4 5v6c0 5 3.41 9.74 8 11 4.59-1.26 8-6 8-11V5l-8-3z"/>',
        'score'     => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>',
        'database'  => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
    ];
    $d = $paths[$key] ?? $paths['dashboard'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
};

$isAdmin = in_array($role, ['admin', 'super_admin', 'staff'], true);
// Sidebar Sistem group + Tetapan page is restricted to admin/super_admin only.
$isSuperAdmin = in_array($role, ['admin', 'super_admin'], true);

if ($isAdmin) {
    $sideTitle = 'Pentadbir';
    $groups = [
        [
            'label' => null,
            'items' => [
                ['label' => 'Dashboard',   'url' => '/admin',               'icon' => 'dashboard'],
                ['label' => 'Skor Kredit', 'url' => '/admin/credit-scores', 'icon' => 'credit'],
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
    ];

    // Sistem > Tetapan & Urus Pengguna hanya untuk admin / super_admin (bukan staff).
    if ($isSuperAdmin) {
        $groups[] = [
            'label' => 'Sistem',
            'items' => [
                ['label' => 'Urus Pengguna',  'url' => '/admin/users',     'icon' => 'users'],
                ['label' => 'Tetapan',        'url' => '/admin/settings',  'icon' => 'settings'],
            ],
        ];
    }
} else {
    $sideTitle = 'Ahli';
    $groups = [
        [
            'label' => null,
            'items' => [
                ['label' => 'Papan Pemuka', 'url' => '/dashboard',    'icon' => 'dashboard'],
                ['label' => 'Skor Kredit',  'url' => '/credit-score', 'icon' => 'credit'],
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
                ['label' => 'Pengeluaran', 'url' => '/withdrawals', 'icon' => 'withdrawals'],
                ['label' => 'Profil', 'url' => '/profile', 'icon' => 'members'],
                ['label' => 'Makluman', 'url' => '/notifications', 'icon' => 'score'],
            ],
        ],
    ];
}

// Build a stable group key for each label so JS can persist collapse state.
$groupKey = static function (int $index, ?string $label) use ($sideTitle): string {
    $base = $label !== null && $label !== ''
        ? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $label) ?? '')
        : 'flat';
    return $sideTitle . ':' . $index . ':' . trim((string) $base, '-');
};

// Compute the "containing group" for the active route so we can auto-expand
// the right group when navigating via direct URL or refresh.
$activeGroupKey = null;
foreach ($groups as $gIndex => $group) {
    foreach ($group['items'] as $item) {
        if ($isActive($item['url'])) {
            $activeGroupKey = $groupKey($gIndex, $group['label']);
            break 2;
        }
    }
}
?>
<aside class="app-sidebar" data-sidebar data-active-group="<?= e((string) $activeGroupKey) ?>">
    <div class="side-title"><?= e($sideTitle) ?></div>
    <nav class="app-nav" aria-label="Navigasi <?= e($sideTitle) ?>">
        <?php foreach ($groups as $gIndex => $group):
            $gKey = $groupKey($gIndex, $group['label']);
            $collapsible = $group['label'] !== null && count($group['items']) > 1;
        ?>
            <?php if ($group['label'] !== null): ?>
                <div class="app-nav-group"
                     data-group
                     data-group-key="<?= e($gKey) ?>"
                     data-default-open="<?= $collapsible && $gKey === $activeGroupKey ? 'true' : ($collapsible ? 'true' : 'false') ?>">
                    <button type="button"
                            class="app-nav-toggle"
                            data-group-toggle
                            aria-expanded="<?= $collapsible ? 'true' : 'false' ?>"
                            aria-controls="grp-<?= e($gKey) ?>"
                            <?= $collapsible ? '' : 'disabled' ?>>
                        <span class="app-nav-label"><?= e($group['label']) ?></span>
                        <?php if ($collapsible): ?>
                            <svg class="app-nav-caret" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        <?php endif; ?>
                    </button>
                    <div id="grp-<?= e($gKey) ?>" class="app-nav-children" role="group">
                        <?php foreach ($group['items'] as $item):
                            // Items inside a labelled group are siblings — highlight
                            // only when the current path matches exactly so we don't
                            // double-mark parent + child at the same time.
                            $itemActive = $isActive($item['url'], true);
                        ?>
                            <a href="<?= e(url($item['url'])) ?>"
                               class="<?= $itemActive ? 'is-active' : '' ?>"
                               <?= $itemActive ? 'aria-current="page"' : '' ?>>
                                <span class="app-nav-icon" aria-hidden="true"><?= $icon($item['icon'] ?? 'dashboard') ?></span>
                                <span><?= e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($group['items'] as $item):
                    // Top-level single items (Dashboard etc.) only highlight on
                    // an exact path match — otherwise the dashboard item remains
                    // lit when navigating to /admin/plans or /admin/payments.
                    $itemActive = $isActive($item['url'], true);
                ?>
                    <a href="<?= e(url($item['url'])) ?>"
                       class="<?= $itemActive ? 'is-active' : '' ?>"
                       <?= $itemActive ? 'aria-current="page"' : '' ?>>
                        <span class="app-nav-icon" aria-hidden="true"><?= $icon($item['icon'] ?? 'dashboard') ?></span>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
