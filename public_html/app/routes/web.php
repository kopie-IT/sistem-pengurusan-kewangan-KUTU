<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CalendarController;
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
    $container->make(AuthService::class)
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

$router->get('/reset-password',  [AuthController::class, 'showResetPassword'], 'reset-password');
$router->post('/reset-password', [AuthController::class, 'resetPassword']);
$router->get('/forgot-password',  [AuthController::class, 'showForgotPassword'], 'forgot-password');
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);

$router->post('/logout', [AuthController::class, 'logout'], 'logout');
$router->get('/logout',  [AuthController::class, 'logout']);

// Authenticated file download (slip)
$router->get('/file/slip/{id}', [FileController::class, 'view'], 'file.slip', [Authenticate::class]);

// ---------------------------------------------------------------------------
// Authenticated (any role)
// ---------------------------------------------------------------------------
$auth = [Authenticate::class, ForcePasswordReset::class];
$admin = [Authenticate::class, ForcePasswordReset::class, Authorize::class => 'admin'];

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

// Profile (member)
$router->get('/profile',  [ProfileController::class, 'index'], 'profile', $auth);
$router->post('/profile', [ProfileController::class, 'update'], null, $auth);

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
$router->post('/admin/plans/{id}/generate', [PlanController::class, 'generateSchedules'], null, $admin);

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
$router->post('/admin/payouts/schedule',       [PayoutController::class, 'createSchedule'], null, $admin);
$router->get('/admin/payouts/{id}/generate',  [PayoutController::class, 'generate'], null, $admin);
$router->post('/admin/payouts/{id}/generate', [PayoutController::class, 'generateStore'], null, $admin);
$router->post('/admin/payouts/{id}/slip',     [PayoutController::class, 'confirmSlip'], null, $admin);

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
