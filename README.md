# Sistem Pengurusan Main Kutu

Aplikasi web untuk mengurus Plan Main Kutu secara digital.

- Vanilla PHP 8.2+
- MySQL/MariaDB
- Apache
- cPanel compatible

## Struktur Projek

```
public_html/
    app/
        config/         # Konfigurasi
        controllers/    # Controller
        core/           # Database, Router, Controller, View
        helpers/        # Helper functions
        middleware/     # Authorization middleware
        models/         # Model
        repositories/   # Repository
        routes/         # Route definitions
        services/       # Business logic services
        validators/     # Input validators
        views/          # View templates
    database/
        migrations/     # SQL migrations
        seeders/        # Data seeders
    public/
        assets/         # CSS, JS, images
    storage/
        logs/           # Application logs
        uploads/        # Secure uploads
    tests/              # Tests
    cron/               # Cron scripts
```

## Default Credentials (Seeded)

Selepas migration dan seeder dijalankan, akaun berikut akan dicipta:

| Role  | Emel                  | Kata Laluan       | Status       |
|-------|-----------------------|-------------------|--------------|
| Admin | `admin@mainkutu.local`  | `Admin@12345`     | First-time login (wajib reset) |
| Member | `member@mainkutu.local`| `Member@12345`    | First-time login (wajib reset) |
| Member (Demo) | `ahmad@mainkutu.local` | `Ahmad@12345` | Demo plan + schedules |

> **PENTING:** Untuk persekitaran production, tukar kata laluan ini SEGERA selepas login pertama dan/atau padamkan seeder.

Akaun demo (`ahmad@mainkutu.local`) dicipta oleh `cron/seed_demo.php` dengan pelan demo PLN-DEMO01 (RM200 x 5 kitaran) supaya aliran bayaran boleh diuji serta-merta.

### Reset Password Flow

The system enforces a password reset in two scenarios:

1. **First-time login** - Akaun yang baru dicipta akan ada flag `must_reset_password = true`. Selepas login, pengguna akan di-redirect ke halaman `/reset-password` untuk menukar kata laluan.
2. **Admin-triggered reset** - Admin boleh reset kata laluan ahli melalui panel admin. Sistem akan jana kata laluan sementara dan hantar token reset ke emel ahli (atau paparkan sekali sahaja untuk admin).

Selepas berjaya reset, flag `must_reset_password` akan dipadamkan dan password akan di-hash semula.

## Docker Desktop

### Keperluan

- Docker Desktop untuk Windows
- Docker Compose (sudah termasuk dalam Docker Desktop)

### Arahan

1. Buka PowerShell atau Command Prompt dalam folder projek.
2. Bina dan jalankan container:

```powershell
docker-compose up --build -d
```

3. Tunggu 10-15 saat untuk MySQL bersedia.
4. Jalankan migration dan seeder:

```powershell
docker exec mainkutu_app php /var/www/html/cron/migrate.php
docker exec mainkutu_app php /var/www/html/cron/seed.php
```

5. Akses aplikasi:

```
http://localhost:8090
```

6. Akses phpMyAdmin:

```
http://localhost:8091
```

Credentials phpMyAdmin:
- Server: db
- Username: root
- Password: root_password

Atau:
- Username: mainkutu
- Password: mainkutu_password

### Hentikan Container

```powershell
docker-compose down
```

### Hentikan dan Padam Data

```powershell
docker-compose down -v
```

## Konfigurasi

1. Salin `.env.example` ke `.env`.
2. Kemas kini nilai mengikut persekitaran anda.

```powershell
cp public_html/.env.example public_html/.env
```

## Development Status

Fasa 1: Project Foundation - Selesai.
Fasa 2: Authentication & Password Reset - Selesai.
Fasa 3: Pelan Kutu & Keahlian (Plan CRUD, join, schedules) - Selesai.
Fasa 4: Bayaran & Pengesahan (single, bulk, verification, ledger, credit score) - Selesai.
Fasa 5: Payout, Admin Fee, Withdrawal, Shortfall, Laporan, Kalendar, Notifikasi - Selesai.

Semua modul P0-P2 dalam `docs/PRD.md` telah dilaksana.

Lihat `docs/IMPLEMENTATION-PLAN.md` untuk butiran fasa.

## Cron Jobs

| Skrip | Tujuan | Cadangan Jadual |
|-------|--------|-----------------|
| `cron/daily.php` | Tanda jadual tertunggak, notifikasi bayaran, skor kredit lewat/gagal, flag payout due | Harian (00:05) |
| `cron/migrate.php` | Jalankan SQL migrations yang belum dilaksana | Manual / deploy |
| `cron/seed.php` | Reset akaun admin/member kepada default | Manual sahaja |

Contoh crontab:

```
5 0 * * * php /var/www/html/cron/daily.php >> /var/www/html/storage/logs/cron.log 2>&1
```

Dalam Docker, boleh ujian secara manual:

```powershell
docker exec mainkutu_app php /var/www/html/cron/daily.php
```
