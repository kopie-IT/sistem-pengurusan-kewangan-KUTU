# Changelog

Sistem Pengurusan Main Kutu

Format mengikut Keep a Changelog.

---

## [Unreleased]

### Changed - Dashboard & Application UI
- Dashboard pentadbir (`/admin`) kini menjadi pusat ringkasan operasi: metrik pelan, ahli, semakan pembayaran, caruman tertunggak, tindakan keutamaan, dan aliran dana.
- Menu `Laporan` kini hanya memaparkan Kewangan, Pelan, dan Ahli; pautan `Papan Pemuka` telah dibuang.
- Laluan lama `/admin/reports` kekal serasi dan mengarah ke `/admin/reports/financial`.
- Shell authenticated, sidebar, kad, ringkasan, senarai tindakan, pautan pantas, dan susun atur mudah alih dikemas kini dengan hierarki visual enterprise yang konsisten.
- Header authenticated kini menghantar pentadbir ke `/admin` dan ahli ke `/dashboard`.

### Added - Lupa Kata Laluan Flow
- Halaman `/forgot-password` (GET/POST) untuk pengguna yang terlupa kata laluan.
- `AuthService::requestPasswordReset()` menjana token sekali guna (hash SHA-256, sah 1 jam) dan mengaudit `auth.password.reset_requested`.
- `AuthController::showForgotPassword()` dan `forgotPassword()` dengan validasi emel dan perlindungan anti-enumerasi (mesej generik).
- View `auth/forgot-password.php` dengan reka letak auth konsisten.
- Pautan `Lupa kata laluan?` pada halaman log masuk kini berfungsi dan mengarahkan ke `/forgot-password`.
- Laluan `/forgot-password` didaftarkan dalam `web.php` (GET untuk papar, POST untuk proses).

### Changed - Sidebar & Login Polish
- Sidebar dijenamakan semula dengan ikon SVG setiap item, garis penunjuk aktif, jarak kumpulan lebih konsisten, dan label kumpulan yang lebih halus.
- Halaman log masuk kini mempunyai butang papar/sembunyi kata laluan (`input-affix` + `app.js` `initPasswordToggle`) dengan keadaan ARIA penuh.

### Added - Menu & Navigation Refactor
- Shared hierarchical sidebar (`app/views/partials/sidebar.php`) dengan navigasi berperingkat (kumpulan + pautan anak yang di-indent) untuk peranan admin dan ahli.
- Helper `partial()` untuk render partial view.
- CSS `.app-nav-group`, `.app-nav-label`, `.app-nav-children` (indentasi + border-left) untuk hierarki menu.
- `.nav-user` (paparan nama pengguna di header) dengan ellipsis pada skrin kecil.
- Menu awam (Utama / Ciri / Bagaimana) dipaparkan sahaja apabila pengguna belum log masuk.

### Changed
- `layouts/main.php` kini auth-aware: papar menu awam sahaja apabila tidak log masuk; papar header ringkas (brand + Makluman + Log Keluar) + sidebar apabila log masuk.
- Semua 15 view admin dibuang inline `.app-sidebar`/`.app-layout`/`.app-content` (kini disediakan oleh layout).

### Fixed
- Account lockout ("Akaun dikunci") dilumpuhkan dalam development mode (`APP_ENV !== production`): semakan `isLocked()` dan `recordFailedLogin()` kini hanya berjalan apabila `APP_ENV=production`.
- `PlanService::getStats()` memanggil `ContributionScheduleRepository::summaryForLedger()` yang tidak wujud (Fatal error pada dashboard laporan). Ditukar kepada `LedgerRepository::balanceSummary()` (aggregation sepatutnya di repository ledger, bukan contribution schedules).
- `PlanController::show()` menghantar `membership` ke view tetapi view `plans/show.php` menjangka `alreadyMember` (bool) dan `memberStatus` (string) → "Undefined variable $alreadyMember". Tetap dengan menghantar `alreadyMember` dan `memberStatus` yang betul.

---

## [0.2.0] - Fasa P0-P2

**Asas Projek (Fasa 1)**
- Struktur projek mengikut PRD section 58; front controller `index.php` + Apache `.htaccess` rewrite.
- Base PDO database layer, Router, Controller, View, dan layout (main + auth).
- Docker Desktop support: `Dockerfile`, `docker-compose.yml`, Apache config, entrypoint, `.env.docker`.
- Design system moden (`app.css` dengan token, components, utility classes); Vanilla JS untuk mobile menu, flash auto-dismiss, form double-submit guard.
- Landing page dengan hero, features, how-it-works, dan CTA section; halaman 404.
- Service container (DI); migration runner + seeder.

**Authentication (Fasa 2)**
- User model, UserRepository, PasswordResetRepository; AuthService dengan login, logout, first-time reset, admin-triggered reset, dan password strength validation.
- ForcePasswordReset + Authenticate middleware; halaman /reset-password (first-time dan token-based).
- AuditService untuk log aktiviti keselamatan; roles, users, password_resets, audit_logs, migrations SQL.

**Skema Pangkalan Data (migration 002_business.sql)**
- 30+ jadual: permissions, role_permissions, members, plans, plan_members, plan_cycles, contribution_schedules, payment_slips, payment_batches, payment_batch_items, payments, payout_schedules, payouts, admin_fee_configs, admin_fee_versions, credit_scores, credit_score_history, credit_score_rules, withdrawal_requests, shortfalls, ledger_transactions, notifications, system_settings, adjustments.
- Semua nilai wang sebagai DECIMAL(15,2); FK dengan CASCADE/SET NULL.

**Model & Repository (18 repository, 16 model)**
- MemberRepository, PlanRepository, PlanMemberRepository, ContributionScheduleRepository, PaymentSlipRepository, PaymentBatchRepository, PaymentRepository, PayoutScheduleRepository, PayoutRepository, AdminFeeConfigRepository, AdminFeeVersionRepository, CreditScoreRepository, WithdrawalRepository, ShortfallRepository, LedgerRepository, NotificationRepository, SystemSettingRepository, PermissionRepository.
- Model berasingan fail (PSR-4): Member, Plan, PlanMember, ContributionSchedule, Payment, PaymentSlip, PaymentBatch, PayoutSchedule, Payout, AdminFeeConfig, CreditScore, CreditScoreHistory, WithdrawalRequest, Shortfall, LedgerTransaction, Notification.

**Perkhidmatan (14 service)**
- AuthService, AuditService, AdminFeeService, CreditScoreService, LedgerService, NotificationService, PlanService, MembershipService, PaymentService, BulkPaymentService, PaymentVerificationService, PayoutService, ShortfallService, FileUploadService.
- Semua operasi kewangan dibungkus dalam DB transaction; aritmetik wang guna BC Math (bcadd/bcsub/bcmul/bcdiv/bccomp).

**Middleware & Routing**
- Authorize middleware dengan parameter peranan (`admin`/`member`/`super_admin`).
- Router menyokong `[Authorize::class => 'admin']` via Container::makeWithParam().
- Route parameters dihantar secara positional supaya nama parameter kaedah controller tidak perlu sepadan dengan placeholder route.
- 17 controller: Admin, Auth, Calendar, CreditScore, Dashboard, File, Home, Member, Notification, Payment, Payout, Plan, Profile, Report, Shortfall, Verification, Withdrawal.
- ~30 view templates Bahasa Melayu mengikut design system (`.card`, `.badge-*`, `.btn-*`, `.form-*`).

**Cron**
- `cron/daily.php`: tanda jadual tertunggak, notifikasi bayaran 3 hari sebelum due, acara skor kredit LATE_PAYMENT/MISSED_PAYMENT, flag payout due dalam 7 hari.
- `cron/seed_demo.php`: akaun demo ahmad@mainkutu.local dengan pelan PLN-DEMO01 (RM200 x 5 kitaran).

**Pengujian**
- `scripts/e2e_smoke.php`: 37 semakan (login admin, 17 laluan admin, CSV export, logout, login member, 13 laluan member, akses admin disekat) - **37/37 PASS**.
- `scripts/payment_flow_test.php`: 14 semakan aliran kewangan (member bulk payment, admin approve, verify batch/payments/ledger/score events/schedules) - **14/14 PASS**.

**Dockerfile**
- `bcmath` extension ditambah (diperlukan untuk bcadd/bcsub/bcmul/bcdiv).

### Changed
- `FileUploadService` kini menggunakan `Config::getInstance()` secara statik (Config adalah singleton dengan private constructor, tidak boleh di-inject via DI Container).
- `CreditScoreService::levelFor()` ditukar kepada static method (dipanggil secara statik oleh cron daily.php).
- Autoloader (index.php, migrate.php, seed.php, seed_demo.php) kini mempunyai case-insensitive fallback untuk direktori `Models`/`models` (kesensitifan case pada Linux).

### Fixed
- **Login gagal (root cause)**: fail `.env` tiada dalam container kerana volume mount override Dockerfile COPY; SESSION_SECURE default true → secure cookie tidak dihantar over HTTP → CSRF/login rosak. Tetap dengan volume mount `.env.docker:/var/www/html/.env:ro`.
- **bcsub undefined**: extension bcmath tiada dalam Docker image. Tetap dengan menambah `bcmath` ke Dockerfile.
- **ReflectionException "Access to non-public constructor of Config"**: FileUploadService type-hint `Config` dalam constructor; Container cuba `new Config()` → fatal. Tetap dengan guna singleton statik.
- **Unknown column 'schedule_id'**: BulkPaymentService INSERT ke payment_batch_items guna `schedule_id` tetapi column sebenar ialah `contribution_schedule_id`. Tetap.
- **Controller/view field name mismatch**: view posts `items[i][schedule_id|plan_id|amount]` tetapi controller parse `schedules[]` format `planId:scheduleId:amount`. Tetap dengan controller parse `items[]`.
- **"Unknown named parameter $id" fatal**: Router hantar route params sebagai array berkunci (named params) → `call_user_func_array` anggap sebagai named arg → nama tak sepadan dengan signature method. Tetap dengan hantar `array_values($params)` (positional).
- **Verification show page tidak papar borang approve**: semakan status `pending`/`resubmit` tetapi batch status sebenar ialah `submitted`. Tetap dengan menerima `submitted`/`pending_verification`/`pending`/`resubmit`/`resubmission`.
- **NotificationRepository is_read TypeError**: bool `false` cast ke empty string oleh PDO. Tetap dengan `(int)` cast.
- **Class not found (case-sensitive Linux)**: semua model dalam satu fail Domain.php; PSR-4 expect satu class per fail + case-sensitive `Models`. Tetap dengan split 16 fail + autoloader fallback.

### Security
- Semua input pengguna divalidasi dan sanitise.
- Query parameterized (PDO prepared statements) di semua repository.
- CSRF `hash_equals` pada semua borang POST.
- Password hashing bcrypt; reset token hashed SHA-256 sebelum disimpan.
- Account lockout selepas 5 percubaan gagal (15 minit).
- Session regeneration selepas login; HttpOnly, SameSite=Strict, Secure (production).
- File upload: validation extension + MIME + size; nama fail disanitise; stored name random (bin2hex random_bytes).
- Authenticated slip download (403 jika ditolak, 404 jika tiada); payout slip admin-only.
- Role-based access (admin/super_admin) via Authorize middleware.
- Security headers (CSP, X-Frame-Options, HSTS, X-Content-Type-Options, Referrer-Policy, Permissions-Policy).

---

# END CHANGELOG
