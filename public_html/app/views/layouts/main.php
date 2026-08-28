<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <title><?= e($title ?? 'Sistem Pengurusan Main Kutu') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/components.css') ?>">
</head>
<body>
    <?php
$authenticated = !empty($_SESSION['user_id']);
$currentUser   = $_SESSION['user_name'] ?? '';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$isAdmin       = in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin', 'staff'], true);
$homeUrl       = $isAdmin ? '/admin' : '/dashboard';

$brandName      = brand_name();
$brandLogoUrl   = brand_logo_url();
$brandInitials  = brand_initials();
$userAvatarUrl  = $authenticated ? user_avatar_url($currentUserId) : null;
$userInitials   = user_initials($currentUser);
?>
<div class="app-shell">
    <header class="app-header">
        <div class="container">
            <nav class="nav" aria-label="Navigasi utama">
                <a href="<?= url($authenticated ? $homeUrl : '/') ?>" class="nav-brand">
                    <?= partial('brand', ['logoUrl' => $brandLogoUrl, 'fallbackInitials' => $brandInitials]) ?>
                    <span><?= e($brandName) ?></span>
                </a>
                    <?php if (!$authenticated): ?>
                        <div class="nav-links" role="menubar">
                            <a href="<?= url('/') ?>" class="nav-link" role="menuitem">Utama</a>
                            <a href="<?= url('/#features') ?>" class="nav-link" role="menuitem">Ciri</a>
                            <a href="<?= url('/#how') ?>" class="nav-link" role="menuitem">Bagaimana</a>
                        </div>
                        <div class="nav-cta">
                            <a href="<?= url('/login') ?>" class="btn btn-primary">Log Masuk</a>
                            <button type="button" class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false" aria-controls="mobileMenu">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="nav-cta">
                            <a href="<?= url('/notifications') ?>" class="btn btn-ghost" aria-label="Pemberitahuan">Makluman</a>

                            <div class="user-menu" data-user-menu>
                                <button type="button" class="user-avatar-btn"
                                        id="userMenuToggle"
                                        data-user-menu-toggle
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                        aria-controls="userMenu"
                                        aria-label="Menu akaun: <?= e($currentUser) ?>">
                                    <?php if ($userAvatarUrl !== null): ?>
                                        <img src="<?= e($userAvatarUrl) ?>" alt="" class="user-avatar-img" loading="lazy">
                                    <?php else: ?>
                                        <span class="user-avatar-bubble" aria-hidden="true"><?= e($userInitials) ?></span>
                                    <?php endif; ?>
                                </button>
                                <div class="user-dropdown" id="userMenu" role="menu" aria-labelledby="userMenuToggle" hidden>
                                    <div class="user-dropdown-header">
                                        <div class="user-dropdown-name"><?= e($currentUser) ?></div>
                                        <div class="user-dropdown-role"><?= e(ucfirst(str_replace('_', ' ', (string) ($_SESSION['user_role'] ?? 'pengguna')))) ?></div>
                                    </div>
                                    <a href="<?= url('/profile') ?>" class="user-dropdown-item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        <span>Kemaskini Profil</span>
                                    </a>
                                    <a href="<?= url('/profile/change-password') ?>" class="user-dropdown-item" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        <span>Tukar Kata Laluan</span>
                                    </a>
                                    <div class="user-dropdown-separator" role="separator"></div>
                                    <a href="<?= url('/logout') ?>" class="user-dropdown-item user-dropdown-item-danger" role="menuitem">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                        <span>Log Keluar</span>
                                    </a>
                                </div>
                            </div>

                            <button type="button" class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false" aria-controls="mobileMenu">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                            </button>
                        </div>
                    <?php endif; ?>
                </nav>
                <div class="mobile-menu" id="mobileMenu" role="menu">
                    <div class="mobile-menu-inner">
                        <?php if (!$authenticated): ?>
                            <a href="<?= url('/') ?>" role="menuitem">Utama</a><a href="<?= url('/#features') ?>" role="menuitem">Ciri</a><a href="<?= url('/#how') ?>" role="menuitem">Bagaimana</a><a href="<?= url('/login') ?>" role="menuitem">Log Masuk</a>
                        <?php else: ?>
                            <div class="mobile-menu-user">
                                <?php if ($userAvatarUrl !== null): ?>
                                    <img src="<?= e($userAvatarUrl) ?>" alt="" class="user-avatar-img" loading="lazy">
                                <?php else: ?>
                                    <span class="user-avatar-bubble" aria-hidden="true"><?= e($userInitials) ?></span>
                                <?php endif; ?>
                                <div>
                                    <strong><?= e($currentUser) ?></strong>
                                    <small><?= e(ucfirst(str_replace('_', ' ', (string) ($_SESSION['user_role'] ?? 'pengguna')))) ?></small>
                                </div>
                            </div>
                            <a href="<?= url($homeUrl) ?>" role="menuitem"><?= $isAdmin ? 'Dashboard' : 'Papan Pemuka' ?></a>
                            <a href="<?= url('/notifications') ?>" role="menuitem">Makluman</a>
                            <a href="<?= url('/profile') ?>" role="menuitem">Kemaskini Profil</a>
                            <a href="<?= url('/profile/change-password') ?>" role="menuitem">Tukar Kata Laluan</a>
                            <a href="<?= url('/logout') ?>" role="menuitem">Log Keluar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        <?php if (!$authenticated): ?>
            <main class="app-main"><?= $content ?></main>
        <?php else: ?>
            <main class="app-main"><div class="app-layout"><?= partial('sidebar') ?><div class="app-content"><?= $content ?></div></div></main>
        <?php endif; ?>
        <?php if (!$authenticated): ?>
            <footer class="app-footer">
                <div class="container">
                    <div class="footer-grid">
                        <div class="footer-brand"><a href="<?= url('/') ?>" class="nav-brand" style="color:#fff;"><?= partial('brand', ['logoUrl' => $brandLogoUrl, 'fallbackInitials' => $brandInitials]) ?><span style="color:#fff;"><?= e($brandName) ?></span></a><p><?= e((new \App\Repositories\AppSettingRepository())->get('brand_tagline', 'Platform pengurusan Main Kutu yang moden, telus dan selamat untuk komuniti anda.')) ?></p></div>
                        <div class="footer-links"><h4>Produk</h4><ul><li><a href="<?= url('/#features') ?>">Ciri</a></li><li><a href="<?= url('/#how') ?>">Bagaimana</a></li></ul></div>
                        <div class="footer-links"><h4>Sokongan</h4><ul><li><a href="#">Bantuan</a></li><li><a href="#">Hubungi Kami</a></li><li><a href="#">FAQ</a></li></ul></div>
                        <div class="footer-links"><h4>Legal</h4><ul><li><a href="#">Terma</a></li><li><a href="#">Privasi</a></li><li><a href="#">Keselamatan</a></li></ul></div>
                    </div>
                    <div class="footer-bottom"><span>&copy; <?= date('Y') ?> Sistem Pengurusan Main Kutu. Hak cipta terpelihara.</span><span>Versi 1.0.0 · MYR (RM)</span></div>
                </div>
            </footer>
        <?php endif; ?>
    </div>
    <script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
