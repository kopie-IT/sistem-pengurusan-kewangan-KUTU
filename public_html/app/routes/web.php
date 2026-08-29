<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AdminSettingsController;
use App\Controllers\AuthController;
use App\Controllers\CalendarController;
use App\Controllers\CaptchaController;
use App\Controllers\CreditScoreController;
use App\Controllers\DashboardController;
use App\Controllers\FileController;
use App\Controllers\HomeController;
use App\Controllers\MemberController;
use App\Controllers\NotificationController;
use App\Controllers\PaymentController;
use App\Controllers\PayoutController;
use App\Controllers\PlanController;
use App\Controllers\ProfileController;
use App\Controllers\ReportController;
use App\Controllers\ShortfallController;
use App\Controllers\UserManagementController;
use App\Controllers\VerificationController;
use App\Controllers\WithdrawalController;
use App\Core\Container;
use App\Core\Router;
use App\Middleware\Authenticate;
use App\Middleware\Authorize;
use App\Middleware\ForcePasswordReset;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\CaptchaService;
use App\Services\SystemSettingService;

// Service container with shared services
$container = new Container();
$container->singleton(UserRepository::class,          fn () => new UserRepository());
$container->singleton(PasswordResetRepository::class, fn () => new PasswordResetRepository());
$container->singleton(AuthService::class,             fn () => new AuthService(
    $container->make(UserRepository::class),
    $container->make(PasswordResetRepository::class)
));
$container->bind(AuthController::class,      fn () => new AuthController(
    $container->make(AuthService::class),
    $container->make(UserRepository::class)
));
$container->bind(DashboardController::class, fn () => new DashboardController(
    $container->make(AuthService::class),
    new \App\Repositories\MemberRepository(),
    new \App\Services\CreditScoreService(
        new \App\Repositories\CreditScoreRepository(),
        new \App\Repositories\MemberRepository()
    )
));

$router = new Router($container);

// ---------------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------------
$router->get('/',          [HomeController::class, 'index'], 'home');
$router->get('/login',     [AuthController::class, 'showLogin'], 'login');
$router->post('/login',    [AuthController::class, 'login']);
$router->get('/register',  [AuthController::class, 'showRegister'], 'register');
$router->post('/register', [AuthController::class, 'register']);

// Public CAPTCHA refresh endpoint (returns JSON). CSRF-exempt because the
// response is idempotent and never mutates user state.
$router->get('/captcha/refresh', [CaptchaController::class, 'refresh'], 'captcha.refresh');

$router->get('/reset-password',  [AuthController::class, 'showResetPassword'], 'reset-password');
$router->post('/reset-password', [AuthController::class, 'resetPassword']);
$router->get('/forgot-password',  [AuthController::class, 'showForgotPassword'], 'forgot-password');
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);

$router->post('/logout', [AuthController::class, 'logout'], 'logout');
$router->get('/logout',  [AuthController::class, 'logout']);

// Authenticated file download (slip)
$router->get('/file/slip/{id}', [FileController::class, 'download'], 'file.slip', [Authenticate::class]);

// ---------------------------------------------------------------------------
// Authenticated (any role)
// ---------------------------------------------------------------------------
$auth = [Authenticate::class, ForcePasswordReset::class];
$admin = [Authenticate::class, ForcePasswordReset::class, Authorize::class => 'admin'];
// Top-level configuration gate (Tetapan Sistem): admin + super_admin only.
$superAdmin = [Authenticate::class, ForcePasswordReset::class, Authorize::class => 'super_admin'];

// Dashboard (role-aware: member vs admin)
$router->get('/dashboard', [DashboardController::class, 'index'], 'dashboard', $auth);

// Plans (member browsing + join)
$router->get('/plans',               [PlanController::class, 'index'], 'plans', $auth);
$router->get('/plans/{id}',          [PlanController::class, 'show'], 'plan.show', $auth);
$router->post('/plans/{id}/join',    [PlanController::class, 'join'], null, $auth);

// Payments (member)
$router->get('/payments',             [PaymentController::class, 'index'], 'payments', $auth);
$router->get('/payments/single/{id}', [PaymentController::class, 'single'], null, $auth);
$router->post('/payments/single',     [PaymentController::class, 'submitSingle'], null, $auth);
$router->get('/payments/bulk',        [PaymentController::class, 'bulk'], 'payments.bulk', $auth);
$router->post('/payments/bulk',       [PaymentController::class, 'submitBulk'], null, $auth);

// Payouts (member view of own upcoming)
$router->get('/payouts/me', [PayoutController::class, 'memberView'], 'payouts.me', $auth);

// Credit score (member)
$router->get('/credit-score', [CreditScoreController::class, 'memberShow'], 'credit-score', $auth);

// Calendar (member)
$router->get('/calendar/contribution',       [CalendarController::class, 'contribution'], 'calendar.contribution', $auth);
$router->get('/calendar/payout',             [CalendarController::class, 'payout'], 'calendar.payout', $auth);

// Profile (any authenticated user — member, staff, admin, super_admin)
$router->get('/profile',  [ProfileController::class, 'index'], 'profile', $auth);
$router->post('/profile', [ProfileController::class, 'update'], null, $auth);
$router->get('/profile/change-password',  [ProfileController::class, 'changePassword'], 'profile.change-password', $auth);
$router->post('/profile/change-password', [ProfileController::class, 'updatePassword'], null, $auth);

// Authenticated avatar streaming (owner-or-admin via FileController).
$router->get('/file/avatar/{id}', [FileController::class, 'userAvatar'], 'file.avatar', $auth);

// Notifications
$router->get('/notifications',            [NotificationController::class, 'index'], 'notifications', $auth);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead'], null, $auth);
$router->post('/notifications/read-all',   [NotificationController::class, 'markAllRead'], null, $auth);

// Withdrawal (member)
$router->get('/withdrawals',           [WithdrawalController::class, 'memberIndex'], 'withdrawals', $auth);
$router->get('/withdrawals/request',   [WithdrawalController::class, 'request'], 'withdrawals.request', $auth);
$router->post('/withdrawals/request',  [WithdrawalController::class, 'submitRequest'], null, $auth);

// ---------------------------------------------------------------------------
// Admin only
// ---------------------------------------------------------------------------
$router->get('/admin',                [AdminController::class, 'index'], 'admin', $admin);
$router->get('/admin/plans',          [PlanController::class, 'adminIndex'], 'admin.plans', $admin);
$router->get('/admin/plans/create',   [PlanController::class, 'create'], null, $admin);
$router->post('/admin/plans',         [PlanController::class, 'store'], null, $admin);
$router->get('/admin/plans/{id}/edit',[PlanController::class, 'edit'], null, $admin);
$router->post('/admin/plans/{id}',    [PlanController::class, 'update'], null, $admin);
$router->post('/admin/plans/{id}/qr', [PlanController::class, 'updateQr'], null, $admin);
$router->post('/admin/plans/{id}/generate', [PlanController::class, 'generateSchedules'], null, $admin);

// Public brand assets.
$router->get('/brand/logo', [FileController::class, 'brandLogo'], 'brand.logo');
$router->get('/brand/qr',   [FileController::class, 'brandQr'],   'brand.qr');

// Per-plan QR (public — used by member view to render the upload card).
$router->get('/plans/{id}/qr', [FileController::class, 'planQr'], 'plan.qr');

$router->get('/admin/members',        [MemberController::class, 'index'], 'admin.members', $admin);
$router->get('/admin/members/create', [MemberController::class, 'create'], null, $admin);
$router->post('/admin/members',       [MemberController::class, 'store'], null, $admin);
$router->get('/admin/members/{id}',   [MemberController::class, 'show'], null, $admin);

$router->get('/admin/payments',            [VerificationController::class, 'queue'], 'admin.payments', $admin);
$router->get('/admin/payments/{id}',       [VerificationController::class, 'show'], null, $admin);
$router->post('/admin/payments/{id}/approve', [VerificationController::class, 'approve'], null, $admin);
$router->post('/admin/payments/{id}/reject',  [VerificationController::class, 'reject'], null, $admin);
$router->post('/admin/payments/{id}/resubmit', [VerificationController::class, 'resubmit'], null, $admin);

$router->get('/admin/payouts',                 [PayoutController::class, 'adminIndex'], 'admin.payouts', $admin);
$router->get('/admin/payouts/schedule',        [PayoutController::class, 'schedule'], 'admin.payouts.schedule', $admin);
$router->post('/admin/payouts/schedule',       [PayoutController::class, 'createSchedule'], null, $admin);
$router->get('/admin/payouts/{id}/generate',  [PayoutController::class, 'generate'], null, $admin);
$router->post('/admin/payouts/{id}/generate', [PayoutController::class, 'generateStore'], null, $admin);
$router->post('/admin/payouts/{id}/slip',     [PayoutController::class, 'confirmSlip'], null, $admin);

// Admin settings (system name, tagline, logo) - admin + super_admin only.
$router->get('/admin/settings',                 [AdminSettingsController::class, 'index'],          'admin.settings', $superAdmin);
$router->post('/admin/settings',                [AdminSettingsController::class, 'update'],         null,             $superAdmin);
$router->post('/admin/settings/blast',          [AdminSettingsController::class, 'sendBlast'],      null,             $superAdmin);
$router->get('/admin/settings/database/export', [AdminSettingsController::class, 'exportDatabase'], 'admin.database.export', $superAdmin);
$router->post('/admin/settings/database/import',[AdminSettingsController::class, 'importDatabase'], 'admin.database.import', $superAdmin);

// Internal user management (admin / super_admin / staff) — admin + super_admin only.
$router->get('/admin/users',                 [UserManagementController::class, 'index'],   'admin.users',          $superAdmin);
$router->get('/admin/users/create',          [UserManagementController::class, 'create'],  'admin.users.create',   $superAdmin);
$router->post('/admin/users',                [UserManagementController::class, 'store'],   null,                    $superAdmin);
$router->get('/admin/users/{id}/edit',       [UserManagementController::class, 'edit'],    null,                    $superAdmin);
$router->post('/admin/users/{id}',           [UserManagementController::class, 'update'],  null,                    $superAdmin);
$router->post('/admin/users/{id}/delete',    [UserManagementController::class, 'destroy'], 'admin.users.delete',   $superAdmin);
$router->post('/admin/users/{id}/reset-password', [UserManagementController::class, 'resetPassword'], 'admin.users.reset', $superAdmin);

$router->get('/admin/credit-scores', [CreditScoreController::class, 'adminIndex'], 'admin.credit-scores', $admin);
$router->get('/admin/credit-scores/{id}', [CreditScoreController::class, 'show'], 'admin.credit-scores.show', $admin);

$router->get('/admin/shortfalls', [ShortfallController::class, 'index'], 'admin.shortfalls', $admin);
$router->post('/admin/shortfalls/{id}/resolve', [ShortfallController::class, 'resolve'], null, $admin);

$router->get('/admin/withdrawals', [WithdrawalController::class, 'adminIndex'], 'admin.withdrawals', $admin);
$router->post('/admin/withdrawals/{id}/decision', [WithdrawalController::class, 'decide'], null, $admin);

$router->get('/admin/reports',            [ReportController::class, 'dashboard'], 'admin.reports', $admin);
$router->get('/admin/reports/financial',  [ReportController::class, 'financial'], null, $admin);
$router->get('/admin/reports/plans',      [ReportController::class, 'plans'], null, $admin);
$router->get('/admin/reports/members',    [ReportController::class, 'members'], null, $admin);
$router->get('/admin/reports/export',     [ReportController::class, 'exportCsv'], null, $admin);

$router->dispatch();
