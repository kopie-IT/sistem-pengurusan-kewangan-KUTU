# Changelog

Sistem Pengurusan Main Kutu

Format mengikut Keep a Changelog.

---

## [Unreleased]

### Added - Pangkalan Data Eksport & Import (DB Backup & Restore)
- Menambah menu **Pangkalan Data** di bawah kategori **Sistem** dalam sidebar navigasi pentadbir ([sidebar.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/partials/sidebar.php)) dengan sokongan pautan terus ke tab `#tab-database`.
- Menambah sokongan deep-linking tab melalui URL hash dalam [app.js](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/public/assets/js/app.js).
- Menambah perkhidmatan `DatabaseBackupService` ([DatabaseBackupService.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/services/DatabaseBackupService.php)) untuk menjana fail dump SQL penuh secara automatik dan mengimport fail SQL melalui transaksi selamat dengan semakan kekangan kunci asing (`FOREIGN_KEY_CHECKS`).
- Menambah endpoint `/admin/settings/database/export` (muat turun fail `.sql`) dan `/admin/settings/database/import` (muat naik dan pulihkan pangkalan data) dalam [AdminSettingsController.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/controllers/AdminSettingsController.php) dan [web.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/routes/web.php).
- Menambah kad antaramuka pengguna (UI) Eksport dan Import SQL di bawah tab Pangkalan Data dalam kategori **Sistem** ([settings.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/admin/settings.php)).
- Menambah rekod jejak audit keselamatan (`AuditService`) untuk aktiviti muat turun dan pemulihan pangkalan data.

### Fixed - Penyelarasan Tarikh Mula & Tarikh Tamat Kitaran Pelan
- Mengemaskini `PlanService::ensurePlanCycles` ([PlanService.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/services/PlanService.php)) untuk mengira dan menyelaraskan `start_date` dan `end_date` bagi setiap kitaran secara seragam merentasi semua ahli pelan.

### Changed - Financial Report Table Layout
- Ruangan `Jenis` disatukan ke dalam ruangan `Pelan` dalam [financial.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/reports/financial.php) menggunakan lencana badge jenis transaksi bersebelahan nama pelan.

### Improved - Equal Height Cards on /plans
- Ditambah kelas susun atur `.plan-card`, `.plan-card-body`, dan `.plan-card-footer` berasaskan Flexbox dalam [components.css](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/public/assets/css/components.css) dan dikemaskini [plans/index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/plans/index.php) agar setiap kad pelan mempunyai saiz dan ketinggian yang sama dan selari dengan kedudukan butang tindakan di bahagian bawah.

### Improved - Action Icon Buttons Sizing & Clarity
- Saiz butang `.btn-icon` ditingkatkan kepada `2.35rem` / `2.15rem` (sm) dan saiz ikon SVG dibesarkan kepada `1.25rem` / `1.15rem` dengan ketebalan strok (`stroke-width: 2`) untuk memastikan kejelasan visual ikon dalam setiap ruangan tindakan jadual.

### Improved - Action Columns Converted to Icon Buttons
- Ditambah gaya utiliti `.btn-icon`, `.btn-icon.btn-sm`, `.table-actions`, dan `.btn-danger-ghost` dalam [app.css](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/public/assets/css/app.css).
- Ditukar semua pautan dan butang tindakan dalam ruangan jadual (action columns) kepada butang ikon moden dengan `title` tooltip dan atribut kebolehcapaian `aria-label`:
  - **Pengurusan Pengguna** ([index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/admin/users/index.php)): Sunting, Reset Kata Laluan, Padam Pengguna.
  - **Pengurusan Pelan** ([admin_index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/plans/admin_index.php)): Lihat Pelan, Lihat Jadual, Sunting Pelan, Jana Jadual.
  - **Pengurusan Ahli** ([index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/members/index.php)): Lihat Butiran Ahli.
  - **Skor Kredit Pentadbir** ([admin_index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/credit_score/admin_index.php)): Lihat Sejarah Skor Kredit.
  - **Pengesahan Bayaran** ([queue.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/verification/queue.php)): Lihat Slip & Sahkan/Butiran.
  - **Jadual Payout** ([admin_index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/payouts/admin_index.php)): Jana Payout.
  - **Pengeluaran Ahli (Admin)** ([admin_index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/withdrawals/admin_index.php)): Butang Hantar Keputusan.
  - **Kekurangan / Shortfall** ([index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/shortfalls/index.php)): Butang Selesai.
  - **Caruman Tertunggak Ahli** ([index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/payments/index.php)): Butang Bayar.
  - **Jadual Payout Ahli** ([member_view.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/payouts/member_view.php)): Lihat Slip.

### Fixed - Namespace Import PDO in ReportController
- Import `use PDO;` dalam [ReportController.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/controllers/ReportController.php) untuk mengelakkan ralat `Class "App\Controllers\PDO" not found`.

### Fixed & Improved - UI/UX Refinements
- **Avatar Dropdown Indicator**: Ditambah ikon panah ke bawah (chevron dropdown icon) di sebelah avatar pengguna dalam topbar header ([main.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/layouts/main.php)).
- **Kad KPI `/admin/payouts`**: Kad statistik disusun dengan tinggi dan lebar seragam serta reka bentuk responsif grid dengan sempadan warna status (`primary`, `success`, `warning`, `danger`) ([admin_index.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/payouts/admin_index.php)).
- **Redesign Tetapan Sistem `/admin/settings`**: Tata letak diperbaharui dengan 4 kad status integrasi, navigasi tab moden, pratonton logo/QR yang lebih kemas, susun atur borang 2-kolum responsif, dan seksyen Email Blast yang teratur ([settings.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/admin/settings.php)).

### Added - Jana Jadual Direct Link & Viewable Schedule Table
- `PlanService::generateSchedules` menjana jadual kitaran (`plan_cycles`), jadual caruman (`contribution_schedules`), dan giliran penerima payout (`payout_schedules`) automatik mengikut giliran dan tarikh kitaran.
- Paparan jadual kitaran di [show.php](file:///c:/Users/home/Documents/Github-project/Sistem_Main_Kutu/public_html/app/views/plans/show.php) memaparkan tarikh bayaran payout, nama penerima giliran kutu, jumlah payout, dan status berwarna (Hijau: Selesai Dibayar, Kuning: Perlu Dibayar, Biru: Berjadual/Akan Datang).

### Changed - Sidebar Skor Kredit Menu Position
- Menu **Skor Kredit** kini diletakkan terus di bawah **Dashboard** (pentadbir di `/admin/credit-scores`) dan **Papan Pemuka** (ahli di `/credit-score`) dalam kumpulan menu pertama sidebar, bukan lagi di bahagian bawah atau dalam kumpulan Akaun.

### Fixed - SQL error `email_blasts table doesn't exist` on fresh install
- `EmailBlastRepository::all()` dan `count()` kini defensif: jika jadual
  belum dicipta, `all()` pulangkan senarai kosong dan `count()` pulangkan
  0. Penambahan `isTableReady()` supaya caller boleh paparkan amaran
  kepada admin tanpa membazir dengan ralat SQL.
- Halaman Tetapan Sistem memaparkan banner amaran jika jadual
  `email_blasts` belum dicipta, dengan arahan untuk jalankan migration
  005.
- Migration 005 (email_blasts) dikekalkan seperti sedia ada; tiada
  perubahan skema diperlukan untuk fix ini.

### Added - Credit Score card on Dashboard
- `DashboardController` kini menyuntik `MemberRepository` +
  `CreditScoreService` dan menghantar skor semasa ahli ke view.
- Kad **Skor Kredit** diletakkan terus di bawah blok "Selamat datang"
  (di luar grid tindakan), mengandungi:
  - **Cincin progres** SVG (0–100) dengan animasi transition.
  - **Lencana tahap** (Cemerlang / Baik / Sederhana / Berisiko /
    Berisiko Tinggi / Belum Dinilai).
  - **Penerangan dinamik** ikut tahap.
  - **Senarai faktor** (+5 caruman, −10/−25 kelewatan, +15 payout sempurna).
  - Butang **Lihat Sejarah Penuh** ke `/credit-score` + kod ahli.
- Kad ringkasan profil di grid juga memaparkan nilai skor.
- Pada dashboard pentadbir, kad ini dipaparkan (admin tidak semestinya
  ahli, tetapi jika ya, skor dipaparkan; jika tidak, kad dilangkau
  tanpa jatuh).

### Added - CAPTCHA (math) + AWS WAF configuration
- `CaptchaService` (math CAPTCHA) menjana soalan tambah / darab (2–9) +
  menyimpan jawapan + token + cap masa dalam session (TTL 10 minit,
  auto-pusing selepas setiap percubaan).
- `CaptchaController::refresh()` mengembalikan JSON untuk butang
  "Tukar soalan" pada form (panggil `fetch` tanpa reload).
- Tetapan CAPTCHA ditambah dalam **Tetapan > Keselamatan & CAPTCHA**
  (tab ke-5):
  - `captcha_enabled` (master switch).
  - 5 toggle per-borang: `login`, `register`, `forgot_password`,
    `reset_password`, `admin_blast`.
  - **AWS WAF** keys (`api_key`, `secret_key`, endpoint API, URL JS)
    disimpan sebagai konfigurasi untuk integrasi AWS WAF Captcha
    masa depan (math CAPTCHA kekal aktif sehingga JS integration
    disambungkan).
- CAPTCHA dipasang pada borang sensitif:
  - **Log masuk** (`/login`)
  - **Daftar akaun** (`/register`)
  - **Lupa kata laluan** (`/forgot-password`)
  - **Reset kata laluan** (`/reset-password`)
  - **Email blast admin** (`/admin/settings/blast`)
  - **Simpan tetapan admin** (`/admin/settings` POST)
- `captcha_field('key')` helper dipanggil dari view; CAPTCHA tidak
  dipaparkan jika togol dimatikan atau tetapan tidak tersedia
  (gagal tertutup).
- Migration `007_captcha_config.sql` menambah baris lalai
  (`captcha_enabled = 1`, semua togol per-borang = 1, AWS WAF keys
  placeholder).
- CSS: `.captcha-field`, `.captcha-question`, `.captcha-refresh`,
  `.alert-warning`, `.alert-info`, `.alert-success`, `.alert-danger`.
- JS: `initCaptchaRefresh()` (butang ↻ + fetch JSON) + visual state
  `.is-error` selepas gagal.
- Audit: tiada event khusus — CAPTCHA ditolak hanya di flash biasa.


### Added - Avatar & Profile Menu in Header
- Header authenticated kini memaparkan butang avatar (atau gelembung inisial jika tiada avatar) di sebelah kanan, dengan menu dropdown: **Kemaskini Profil**, **Tukar Kata Laluan**, dan **Log Keluar**.
- Fungsi `user_avatar_url($userId)` dan `user_initials($name)` ditambah dalam `app/helpers/functions.php` untuk kegunaan global (header, mobile menu, halaman profil).
- Mobile menu juga memaparkan avatar + nama + peranan di bahagian atas, disusuli pautan Dashboard, Pemberitahuan, Profil, Tukar Kata Laluan, Log Keluar.

### Added - User Profile (Personal Info + Avatar Upload)
- Halaman `/profile` dibina semula:
  - **Muat naik avatar** (PNG / JPG / WebP / GIF, ≤ 2 MB) dengan pratonton masa nyata sebelum simpan.
  - **Nama penuh**, **nombor telefon** (validasi format MY), **no. KP** (jika ahli), **alamat** (jika ahli) — semua dengan validasi panjang & format.
- Halaman baharu `/profile/change-password` dengan tiga medan (kata laluan semasa, baru, pengesahan), validasi minimum 8 aksara, dan mata laluan yang mesti berbeza.
- Avatar disimpan di `storage/uploads/avatars/` dengan konvensi nama `avatar_<userId>_<timestamp>_<rand>.<ext>`; avatar lama dipadam apabila diganti.
- Laluan `/file/avatar/{id}` (auth-gated) — hanya pemilik avatar atau admin boleh melihat; mengakses avatar orang lain akan dapat 403.
- Migration `006_user_avatar.sql` menambah kolum `users.avatar_path`.
- `ProfileController` baharu: `index`, `update`, `changePassword`, `updatePassword` + `handleAvatarUpload` (validasi mime, saiz, extension).
- `UserRepository::updateProfile()`, `updateAvatar()`, `getAvatarPath()` ditambah.
- Audit `profile.updated` dan `profile.password.changed`.


### Added - Urus Pengguna (Admin & Staf)
- Halaman `/admin/users` untuk admin/super_admin mengurus akaun pentadbiran dalaman (admin / super_admin / staff).
- `UserManagementController` dengan senarai + cari, cipta, sunting (peranan & status), reset kata laluan sementara, dan padam.
- Pautan dari sidebar: **Sistem > Urus Pengguna** (admin/super_admin sahaja; staf tidak nampak).
- Sekatan keselamatan: tidak boleh memadam super admin terakhir, tidak boleh menggantung diri sendiri, tidak boleh reset diri sendiri.
- Audit: `user.admin.create`, `user.admin.update`, `user.admin.delete`, `user.admin.password_reset`.

### Added - Dashboard Giliran Dapat Kutu
- Kad baharu **"Giliran dapat kutu hari ini"** di dashboard pentadbir.
- Menyenaraikan semua `payout_schedules` yang jatuh pada tarikh hari ini dan 7 hari akan datang, lengkap dengan nama penerima, pelan, kod ahli, dan jumlah payout.
- Pengiraan jumlah hari ini + jumlah 7 hari (menggunakan BC Math).
- Kad kosong dengan tarikh payout seterusnya jika tiada payout hari ini.

### Added - Konfigurasi Email Blast + wap.net (Tetapan Sistem)
- Halaman `/admin/settings` (admin/super_admin) ditambah 3 tab baharu:
  - **Email Blast** — toggle aktif, dari nama/emel, reply-to, footer, subjek lalai.
  - **wap.net (WhatsApp Gateway)** — toggle, API URL, API key, sender id, default template.
  - **Operasi & Hubungan** — telefon & emel helpdesk sistem.
- Borang composer **"Hantar Email Blast"** di bahagian bawah halaman tetapan untuk menghantar emel pukal dengan sasaran (semua / ahli / admin / super_admin / staff).
- Setiap blast disimpan ke `email_blasts` (baru) dengan status `sent`/`partial`/`failed` dan salinan dihantar juga ke notifikasi dalam-app.
- Migration `005_system_config.sql` menambah default rows untuk `system_settings` + jadual `email_blasts`.
- Bahagian pratonton/cardboard dengan status integrasi terkini (logo, QR, email blast, wap.net).

### Fixed - Sidebar highlight (hanya item aktif)
- Item sidebar kini ditanda `is-active`/`aria-current="page"` hanya pada item yang persis dengan URL sekarang (sebelum ini `/admin` kekal menyala bila navigasi ke `/admin/plans`).
- `app.js` `highlightActiveNav()` kini defensif: jika lebih dari satu sidebar item menyimpan `.is-active`, hanya yang URL-nya tepat disimpan.

### Added - Lebih Banyak Data Sampel
- `cron/seed_demo2.php` menambah 6 ahli tambahan (`faisal@`, `lina@`, `arif@`, `nadia@`, `khairul@`, `yusof@mainkutu.local`) dengan skor kredit 45–92.
- Tambah pelan ketiga `PLN-KUTU3` (RM500 sebulan × 12 kitaran) dengan semua ahli didaftarkan.
- Jana `payout_schedules` untuk setiap pelan/ahli dengan tarikh payout merebak dari hari ini sehingga +7 hari — supaya dashboard "Giliran dapat kutu" papar data realistik dengan serta-merta.
- Welcome notification untuk ahli baharu.
- Boleh dijalankan berulang (idempotent) — `php cron/seed_demo2.php`.

### Added - Realistic Transaction Sample Data (seed_demo3)
- Skrip baharu `cron/seed_demo3.php` menjana transaksi sampel yang lengkap dan realistik merentasi 10 kumpulan entiti, melengkapi data ahli/pelan sedia ada daripada `seed_demo.php` + `seed_demo2.php`.
- **`plan_cycles`** — satu kitaran sebulan-kalender setiap pelan (4 bulan lepas + bulan semasa + 12 bulan hadapan); status `completed` / `active` / `upcoming` mengikut tarikh.
- **`admin_fee_configs` + `admin_fee_versions`** — yuran pentadbir RM12 (pelan kecil) / RM25 (pelan besar) per pelan, dengan versi audit.
- **`contribution_schedules`** — backfill jadual caruman untuk setiap kombinasi ahli × kitaran pelan yang hilang.
- **`payment_slips` + `payment_batches` (approved) + `payment_batch_items` + `payments` (approved) + `ledger_transactions` (contribution + admin_fee)** untuk setiap kitaran lepas × ahli × pelan — supaya `/admin/payments`, `/admin/verification` (sejarah) dan laporan kewangan memaparkan data.
- **Current-month `pending_verification`** batches untuk ~40% ahli supaya barisan semakan pentadbir tidak kosong.
- **`payouts` + `ledger_transactions` (payout + admin_fee)** untuk semua `payout_schedules` yang tarikhnya ≤ hari ini (status `paid` dengan `paid_date`, `payment_reference`, `payment_slip_id`); baris `scheduled` yang hampir (≤ 7 hari) ditanda `due` supaya tersenarai dalam senarai tindakan admin.
- **`shortfalls`** — satu `open` (85% kutipan) + satu `resolved` setiap pelan untuk paparan `/admin/shortfalls`.
- **`withdrawal_requests`** — 1 `completed` + 2 `pending` untuk paparan `/admin/withdrawals`.
- **`notifications`** — `payment.approved`, `payout.paid`, `payment.pending_reminder`, `shortfall.alert` untuk ahli.
- **`email_blasts`** — 2 rekod log sampel untuk kegunaan laporan.
- Ringkasan akhir memaparkan kiraan baris untuk 14 jadual.
- Sepenuhnya idempotent: probe-before-insert untuk semua entiti + `batch_no` unik (format `BATCH-YYYYMMDD-MEMBERID-PLANID`). Boleh dijalankan berulang tanpa data pendua.
- Cara jalan: `php cron/seed_demo3.php` (selepas `seed_demo.php` + `seed_demo2.php`).

### Changed - Sidebar Accordion & Admin-Only Tetapan
- **Sidebar accordion**: `initSidebarCollapse()` dalam `public/assets/js/app.js` kini berkelakuan seperti accordion — apabila pengguna membuka satu kumpulan, semua kumpulan lain akan tertutup dahulu. Kumpulan yang mengandungi laluan aktif kekal dibuka selepas navigasi langsung. State kekal disimpan di `localStorage` (`mk:sidebar:groups:v1`).
- **Tetapan Sistem (admin sahaja)**: halaman `/admin/settings` (GET + POST) kini hanya boleh diakses oleh peranan `admin` dan `super_admin` (staf dihalang). Akses dikawal oleh gate baru `Authorize::class => 'super_admin'` dalam `app/routes/web.php` (middleware menerima `['admin', 'super_admin']` sahaja, tiada `staff`).
- **Sidebar Sistem group**: kumpulan `Sistem > Tetapan` dalam `app/views/partials/sidebar.php` hanya dipaparkan untuk `admin` dan `super_admin` — staf tidak lagi nampak pautan ini. Akses terus ke URL `/admin/settings` oleh staf akan di-redirect ke `/dashboard`.

### Added - Admin Settings, Brand & QR Management
- Halaman `/admin/settings` untuk pentadbir mengurus **nama sistem**, **tagline**, **logo**, dan **QR pembayaran** dengan pratonton masa nyata.
- `AdminSettingsController` mengendalikan muat naik logo + QR sistem (PNG/JPG/SVG/WEBP, ≤2 MB), penyingkiran, dan audit.
- Pengemaskinian storan: jadual `app_settings` (`app_name`, `brand_tagline`, `logo_path`, `payment_qr_path`).
- QR khusus pelan: kolum `plans.payment_qr_path` ditambah; admin boleh memuat naik/ membuang QR setiap pelan dari `/admin/plans/{id}/edit`. Ahli lihat QR khusus pelan pada halaman pelan, dan boleh memuat naik slip selepas mengimbas.
- Laluan awam untuk aset brand: `/brand/logo`, `/brand/qr`, `/plans/{id}/qr` (tiga-tiga disiarkan dengan Content-Type dan Cache-Control yang sesuai).
- `PlanController::updateQr()` mengendalikan upload/remove per-plan dengan audit `plan.qr.updated`.
- Helper `brand_name()`, `brand_logo_url()`, `brand_initials()` untuk kegunaan global + partial `partials/brand.php`.
- Semua halaman auth (`login`, `register`, `forgot-password`, `reset-password`) + header authenticated, footer, dan paparan brand kini konsisten menggunakan identiti yang dikonfigurasi.

### Added - Sidebar Expand/Collapse
- Kumpulan sidebar kini boleh dilipat/dibuka dengan butang toggle dan penjagaan state `localStorage` (`mk:sidebar:groups:v1`).
- Kumpulan yang mengandungi laluan aktif sentiasa dibuka supaya item aktif tidak tersembunyi selepas navigasi langsung.
- Penanda `data-group-key` + `data-active-group` untuk kebolehcapaian (`aria-expanded`, `aria-controls`).
- Gaya CSS baru untuk butang toggle, animasi max-height (250ms ease-in-out) dan penjagaan penumpukan CSS hierarki.

### Fixed
- `FileController::view(int)` ditukar kepada `FileController::download(int)` supaya tidak bertindih dengan `Controller::view(string, array)`. Route `/file/slip/{id}` dikemas kini. (Sebelum ini latent — tidak aktif sehingga laluan brand ditambahkan.)

### Database Changes
```sql
CREATE TABLE IF NOT EXISTS `app_settings` (
    `key`        VARCHAR(100) NOT NULL,
    `value`      TEXT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `app_settings` (`key`, `value`) VALUES
    ('app_name', 'Sistem Pengurusan Main Kutu'),
    ('brand_tagline', 'Platform pengurusan Main Kutu yang moden, telus dan selamat.'),
    ('logo_path', NULL),
    ('payment_qr_path', NULL);

ALTER TABLE `plans`
    ADD COLUMN IF NOT EXISTS `payment_qr_path` VARCHAR(255) NULL DEFAULT NULL AFTER `admin_fee_percent`;
```

### Fixed - Payouts Schedule GET
- `/admin/payouts/schedule` (GET) sebelum ini mengembalikan 404 kerana hanya laluan POST didaftarkan. Laluan GET ditambah dengan view `payouts/admin_schedule.php` (borang untuk pilih pelan, ahli, tarikh, jumlah).

---

## [Unreleased]

### Changed - Dashboard & Application UI
- Dashboard pentadbir (`/admin`) kini menjadi pusat ringkasan operasi: metrik pelan, ahli, semakan pembayaran, caruman tertunggak, tindakan keutamaan, dan aliran dana.
- Menu `Laporan` kini hanya memaparkan Kewangan, Pelan, dan Ahli; pautan `Papan Pemuka` telah dibuang.
- Laluan lama `/admin/reports` kekal serasi dan mengarah ke `/admin/reports/financial`.
- Shell authenticated, sidebar, kad, ringkasan, senarai tindakan, pautan pantas, dan susun atur mudah alih dikemas kini dengan hierarki visual enterprise yang konsisten.
- Header authenticated kini menghantar pentadbir ke `/admin` dan ahli ke `/dashboard`.

### Added - Lupa Kata Laluan Flow
- `AuthService::requestPasswordReset()` menjana token sekali guna (hash SHA-256, sah 1 jam) dan mengaudit `auth.password.reset_requested`.
- `AuthController::showForgotPassword()` dan `forgotPassword()` dengan validasi emel dan perlindungan anti-enumerasi (mesej generik).
- View `auth/forgot-password.php` dengan reka letak auth konsisten.
- Pautan `Lupa kata laluan?` pada halaman log masuk kini berfungsi dan mengarahkan ke `/forgot-password`.
- Laluan `/forgot-password` didaftarkan dalam `web.php` (GET untuk papar, POST untuk proses).

### Fixed - Credit Scores Query
- `CreditScoreRepository::all()` dirujuk `m.full_name` tetapi lajur itu tidak wujud pada jadual `members`. Tukar kepada `u.name` melalui `INNER JOIN users` supaya halaman `/admin/credit-scores` dapat dimuat.

### Added - Akaun Admin & Staf
- Peranan `staff` ditambah pada jadual `roles` (akses seperti admin tetapi bukan ahli).
- Akaun pentadbir:
  - `admin@mainkutu.local` / `Admin@12345` (peranan `admin`)
  - `superadmin@mainkutu.local` / `Super@12345` (peranan `super_admin`)
  - `staff@mainkutu.local` / `Staff@12345` (peranan `staff`)
- `Authorize` middleware, `User::isAdmin()`, `Controller::isAdmin()`, sidebar, header, dan semua pengawal kini menerima ketiga-tiga peranan (`admin`, `super_admin`, `staff`) untuk kawasan pentadbiran.
- Ahli biasa masih dihalang dari kawasan admin (disahkan melalui redirect 302).

### Database Changes
```sql
INSERT IGNORE INTO roles (name, slug, description, created_at, updated_at) VALUES
  ('Super Admin', 'super_admin', 'Akaun pentadbir tertinggi (akses penuh)', NOW(), NOW()),
  ('Staff',         'staff',       'Akaun staf (akses seperti admin)', NOW(), NOW());

INSERT INTO users (name, email, password, role_id, status, must_reset_password, failed_login_count, locked_until, created_at, updated_at)
VALUES
  ('Administrator', 'admin@mainkutu.local',      '<bcrypt>', (SELECT id FROM roles WHERE slug='admin'),       'active', 0, 0, NULL, NOW(), NOW()),
  ('Super Admin',   'superadmin@mainkutu.local', '<bcrypt>', (SELECT id FROM roles WHERE slug='super_admin'), 'active', 0, 0, NULL, NOW(), NOW()),
  ('Staf Sistem',   'staff@mainkutu.local',      '<bcrypt>', (SELECT id FROM roles WHERE slug='staff'),       'active', 0, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), updated_at = NOW();
```

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
