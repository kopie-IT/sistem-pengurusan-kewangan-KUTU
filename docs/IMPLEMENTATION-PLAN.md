# Implementation Plan

Sistem Pengurusan Main Kutu — Vanilla PHP / cPanel

---

## Phase 1: Project Foundation

**Objective:**
Mewujudkan struktur projek, konfigurasi asas, database connection, router, controller, view layout, dan foundation authentication.

**Files affected:**
- `public_html/.env.example`
- `public_html/app/config/`
- `public_html/app/helpers/`
- `public_html/app/core/Database.php`
- `public_html/app/core/Router.php`
- `public_html/app/core/Controller.php`
- `public_html/app/core/View.php`
- `public_html/app/views/layouts/`
- `public_html/app/views/errors/`
- `public_html/app/routes/web.php`
- `public_html/index.php`
- `public_html/.htaccess`

**Database changes:**
- Tiada migration dibuat dalam fasa ini.

**Business rules:**
- Tiada business rule kewangan dalam fasa ini.
- Semua konfigurasi sensitif mesti melalui `.env`.
- Credentials database tidak boleh di-commit.

**Implementation steps:**
1. Cipta struktur folder projek.
2. Cipta `.env.example`.
3. Cipta `Config.php` untuk membaca `.env`.
4. Cipta `Database.php` menggunakan PDO.
5. Cipta `Router.php` dengan front-controller pattern.
6. Cipta `Controller.php` asas.
7. Cipta `View.php` dan layout utama.
8. Cipta `functions.php` helpers.
9. Cipta `index.php` entry point.
10. Cipta `.htaccess` rewrite.
11. Cipta `AuthController.php` foundation dan login page.
12. Jalankan smoke test.

**Tests:**
- Smoke test: halaman utama dapat dilayari.
- Smoke test: halaman login dapat dilayari.

**Acceptance criteria:**
- URL root memaparkan halaman sistem.
- `/login` memaparkan borang login.
- Tiada error PHP.
- `.env.example` ada dalam repo tetapi `.env` tidak di-commit.

**Status:** Selesai.

---

## Phase 2: Authentication and Roles

**Objective:**
Mewujudkan sistem authentication yang lengkap dengan:
- Login / logout menggunakan bcrypt.
- Account lockout selepas percubaan gagal.
- Session regeneration selepas login.
- First-time password reset flow (auto-redirect ke /reset-password).
- Admin-triggered password reset (token-based).
- Role-based access (admin / member) sebagai asas untuk fasa seterusnya.
- Audit trail untuk aktiviti authentication.

**Files affected:**
- `public_html/database/migrations/001_init_auth.sql`
- `public_html/database/seeders/001_init_users.sql`
- `public_html/cron/migrate.php`
- `public_html/cron/seed.php`
- `public_html/app/models/User.php`
- `public_html/app/repositories/UserRepository.php`
- `public_html/app/repositories/PasswordResetRepository.php`
- `public_html/app/services/AuthService.php`
- `public_html/app/services/AuditService.php`
- `public_html/app/middleware/Authenticate.php`
- `public_html/app/middleware/ForcePasswordReset.php`
- `public_html/app/controllers/AuthController.php`
- `public_html/app/controllers/DashboardController.php`
- `public_html/app/views/auth/login.php`
- `public_html/app/views/auth/register.php`
- `public_html/app/views/auth/reset-password.php`
- `public_html/app/views/dashboard/index.php`
- `public_html/app/core/Container.php`
- `public_html/app/core/Router.php` (middleware + DI)
- `public_html/app/config/Config.php` (env + system env)
- `public_html/app/routes/web.php`
- `public_html/public/index.php` (session config)
- `public_html/.env.example` (+ AUTH_* keys)
- `.env.docker` (+ AUTH_* keys)
- `README.md` (default credentials)

**Database changes:**
- Tambah table `roles` (id, name, slug, description, timestamps).
- Tambah table `users` dengan fields: id, name, email, email_verified_at, password (bcrypt), role_id, status (active/inactive/suspended), must_reset_password, last_login_at, last_login_ip, failed_login_count, locked_until, remember_token, timestamps. Indexes pada email, role_id, status.
- Tambah table `password_resets` (id, user_id, token_hash, expires_at, used_at, created_by, created_at).
- Tambah table `audit_logs` (id, user_id, action, entity, entity_id, meta JSON, ip, user_agent, created_at).
- Tambah table `migrations` untuk migration tracking.

**Business rules:**
- Kata laluan minimum 8 aksara, mesti ada huruf besar, huruf kecil, dan nombor.
- 5 percubaan login gagal mengunci akaun selama 15 minit.
- Reset token valid selama 1 jam, single-use, dan disimpan sebagai SHA-256 hash.
- Session regenerate selepas login.
- Force password reset pada first-time login atau selepas admin reset.
- Default seeded users: admin@mainkutu.local / Admin@12345 dan member@mainkutu.local / Member@12345 (kedua-dua `must_reset_password = 1`).

**Implementation steps:**
1. Tulis SQL migration untuk roles, users, password_resets, audit_logs.
2. Tulis migration runner (`cron/migrate.php`).
3. Tulis User model, UserRepository, PasswordResetRepository.
4. Tulis AuthService dengan semua method yang diperlukan.
5. Tulis AuditService untuk log.
6. Tulis middleware Authenticate dan ForcePasswordReset.
7. Update Router untuk support middleware dan DI container.
8. Update AuthController dengan login, logout, register, reset-password flows.
9. Tulis view untuk reset-password (support first-time dan token).
10. Tulis DashboardController dan view.
11. Update index.php untuk session config.
12. Tulis seeder (`cron/seed.php`) untuk default admin dan member.
13. Update README dengan default credentials.
14. Smoke test.

**Tests:**
- Login dengan credentials betul.
- Login dengan password salah (kena lock selepas 5 percubaan).
- First-time login → forced reset → success.
- Admin-triggered reset dengan token.
- Token expired/invalid ditolak.
- Constant-time CSRF token check.
- Session cookie attributes (HttpOnly, SameSite).

**Acceptance criteria:**
- [x] Default admin dan member dicipta melalui seeder.
- [x] Login berfungsi dengan bcrypt verification.
- [x] First-time login force redirect ke /reset-password.
- [x] Reset password berjaya memadam flag `must_reset_password`.
- [x] Audit log merekodkan login, logout, dan reset events.
- [x] README didokumentasikan dengan jelas.

**Status:** Selesai (foundations).

---

## Phase 3: Member Management

**Objective:**
CRUD member dan profil pengguna.

**Status:** Selesai

---

## Phase 4: Plan Management

**Objective:**
CRUD Plan, konfigurasi membership, payout, admin fee, credit score.

**Status:** Selesai

---

## Phase 5: Contribution Schedule

**Objective:**
Jana contribution schedule berdasarkan plan cycle.

**Status:** Selesai

---

## Phase 6: Single Payment

**Objective:**
Member boleh membuat bayaran single contribution dengan slip.

**Status:** Selesai

---

## Phase 7: Bulk Payment and Payment Slip

**Objective:**
Member boleh memilih multiple outstanding dan membuat satu bulk payment dengan satu slip.

**Status:** Selesai

---

## Phase 8: Payment Verification

**Objective:**
Admin verify/reject/resubmit payment dengan audit trail dan ledger.

**Status:** Selesai

---

## Phase 9: Payout Schedule / Calendar

**Objective:**
Jadual payout dan kalendar untuk member.

**Status:** Selesai

---

## Phase 10: Fixed Payout

**Objective:**
Implement Fixed Payout sebagai default.

**Status:** Selesai

---

## Phase 11: Actual Collection

**Objective:**
Support Actual Collection payout mode.

**Status:** Selesai

---

## Phase 12: Admin Fee

**Objective:**
Versioned admin fee applied during payout.

**Status:** Selesai

---

## Phase 13: Payout Slip

**Objective:**
Admin upload payout slip dan member view selepas confirm.

**Status:** Selesai

---

## Phase 14: Shortfall

**Objective:**
Rekod shortfall dan resolution.

**Status:** Selesai

---

## Phase 15: Credit Score

**Objective:**
Credit score engine dengan rules, history, dan audit.

**Status:** Selesai

---

## Phase 16: Score Recovery

**Objective:**
Score recovery melalui positive behaviour.

**Status:** Selesai

---

## Phase 17: Withdrawal

**Objective:**
Withdrawal request workflow.

**Status:** Selesai

---

## Phase 18: Notifications

**Objective:**
In-app dan email notification.

**Status:** Selesai

---

## Phase 19: Reports

**Objective:**
Dashboard, reports, dan CSV export.

**Status:** Selesai

---

## Phase 20: Security Hardening

**Objective:**
CSRF, rate limiting, secure headers, file security, audit review.

**Status:** Selesai

---

## Phase 21: Testing / UAT

**Objective:**
Unit, integration, financial, security tests.

**Status:** Selesai

---

# END IMPLEMENTATION PLAN
