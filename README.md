# SCP (School Class Progress) — Sistem Manajemen Pembelajaran & Presensi SMK

Sistem terintegrasi untuk manajemen jadwal pelajaran berputar (`Putaran 1` & `Putaran 2`), presensi KBM harian, jurnal mengajar guru, perizinan siswa, kenaikan kelas, dan rekapitulasi kehadiran berbasis peran untuk SMK Negeri 1 Bantul.

---

## 1. Teknologi & Versi (Stack)

- **Backend**: Laravel 13.x (`^13.8`, PHP `^8.3`), Laravel Sanctum `^4.0`, `phpoffice/phpspreadsheet ^5.9`, `smalot/pdfparser ^2.12`
- **Database**: SQLite 3 (Pengujian & pengembangan teruji) / MySQL 8.0+ didukung
- **Frontend Web**: Blade Templates, Tailwind CSS `^3.1` (`@tailwindcss/forms`, font lokal `@fontsource/plus-jakarta-sans` & `@fontsource/jetbrains-mono`), Alpine.js `^3.4` (Operational Console UI dengan mode terang & gelap)
- **Asset Bundler**: Vite `8.x` (`laravel-vite-plugin ^3.1`)
- **Mobile Client**: Native iOS (SwiftUI, REST API `/api/v1`)
- **Testing**: PHPUnit `12.x` (`phpunit/phpunit ^12.5`)

---

## 2. Prasyarat Sistem (Prerequisites)

- **PHP**: `^8.3` dengan ekstensi `pdo_sqlite`, `mbstring`, `openssl`, `curl`, `xml`, `zip`, `gd`, `bcmath`.
- **Composer**: `^2.2`
- **Node.js & npm**: Node.js `^20.x` & npm `^10.x`
- **Web Server**: Nginx / Caddy / Apache dengan PHP-FPM, atau `php artisan serve` untuk pengembangan lokal.

---

## 3. Instalasi dari Nol (Installation from Scratch)

### 3.1. Clone Repositori
```bash
git clone git@github.com:Visipir/scp.git
cd scp
```

> [!NOTE]
> Branch utama repositori adalah `main`.

### 3.2. Instal Dependensi
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 3.3. Konfigurasi Lingkungan
Salin berkas template tanpa secret dan generate `APP_KEY`:
```bash
cp .env.example .env
php artisan key:generate
```
Pastikan pengaturan dasar pada `.env`:
```ini
APP_NAME=SCP
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api-scp.example.sch.id

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```
Siapkan berkas database SQLite kosong jika belum ada:
```bash
touch database/database.sqlite
chmod 664 database/database.sqlite
chown -R www-data:www-data storage bootstrap/cache database
```

### 3.4. Migrasi Skema Database & Storage Link
```bash
php artisan migrate --force
php artisan storage:link
```

### 3.5. Pembuatan Akun Awal (Bootstrap)
Jalankan `RoleSeeder` dan `RealAccountsSeeder` dengan kata sandi via environment variabel tanpa menuliskan secret ke dalam repositori:
```bash
php artisan db:seed --class=RoleSeeder --force
SEED_DEFAULT_PASSWORD="<KATA_SANDI_AMAN>" php artisan db:seed --class=RealAccountsSeeder --force
```

> **Catatan Keamanan:** Akun awal yang dibuat melalui `RealAccountsSeeder` berstatus `must_change_password = true`. Pengguna **wajib mengganti kata sandi pada login pertama** sebelum dapat mengakses dashboard atau fitur lainnya. Jika `SEED_DEFAULT_PASSWORD` kosong, seeder menolak eksekusi.

### 3.6. Menjalankan Aplikasi
- **Lingkungan Produksi**: Konfigurasikan web server menuju direktori `public/`:
  ```nginx
  server {
      listen 80;
      server_name example.com;
      root /var/www/your-app/public;

      index index.php index.html;

      location / {
          try_files $uri $uri/ /index.php?$query_string;
      }

      location ~ \.php$ {
          include snippets/fastcgi-php.conf;
          fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
      }

      location ~ /\.(?!well-known).* {
          deny all;
      }
  }
  ```
- **Lingkungan Pengembangan**:
  ```bash
  composer run dev
  ```
  Atau secara terpisah: `php artisan serve --host=0.0.0.0 --port=8000` dan `npm run dev`.

---

## 4. Struktur Direktori Proyek

```text
/
├── app/
│   ├── Console/Commands/        # Artisan commands (import, reset kredensial, proteksi akun)
│   ├── Http/
│   │   ├── Controllers/         # Web Controllers (Waka, Superadmin, Guru, Kesiswaan, Siswa)
│   │   │   └── Api/V1/          # REST API Controllers (Sanctum-authenticated)
│   │   ├── Middleware/          # RoleMiddleware, CheckScheduleLock, SecurityHeaders, password guard
│   │   └── Resources/Api/V1/    # JSON API Transformers (V1 Spec)
│   ├── Models/                  # Eloquent Models (User, Schedule, Journal, LeaveRequest, Room, dsb.)
│   └── Services/                # Business logic (Schedule, Journal, LeaveRequest, ClassPromotion, dsb.)
├── config/                      # Konfigurasi aplikasi (protected_accounts.php, auth, mail)
├── database/
│   ├── migrations/              # Skema & migrasi database (termasuk role Superadmin & kategori izin)
│   └── seeders/                 # Seeders (RoleSeeder, RealAccountsSeeder, UserSeeder, dsb.)
├── docs/                        # Dokumentasi teknis, API spec, checklist UKK
├── public/                      # Entry point (index.php), Vite build assets, favicon, logo WebP
├── resources/
│   ├── css/ & js/               # Tailwind CSS tokens, Alpine.js stores, accessible validation helpers
│   └── views/                   # Blade templates (dashboard, teacher, attendance, classes, schedules, waka, users, dsb.)
├── routes/                      # Route definitions (web.php, api.php, auth.php)
├── storage/app/private/         # Direktori privat (backups, credentials CSV, gitignored)
└── tests/                       # Automated test suite (Feature & Unit)
```

---

## 5. Peran Pengguna & Fitur Utama

| Peran | ID | Fitur Utama |
|---|---|---|
| **Superadmin** | `5` | Paritas akses monitoring & pengguna + konfigurasi teknis eksklusif: SMTP Email (`/system-configuration/email`), Google Gemini API (`/waka/settings`), dan tampilan latar login 2-kolom (`/waka/settings/login-background`). |
| **Waka Kurikulum** | `1` | Pengaturan kurikulum (`/waka/settings`: siklus `Putaran 1`/`Putaran 2`, kunci jadwal, aturan jam jurnal), Master Jadwal & Impor FET satu langkah (`/schedules` & `/waka/schedules/import`), AI Importer (`/ai-importer`), Impor Pegawai & Wali/BK, Manajemen Pengguna (`/users`: filter Guru/Siswa, reset password manual/acak, bulk reset guru), Manajemen Ruangan (`/rooms`), Kenaikan Kelas & Mutasi (`/classes/promotion`), Rekapitulasi Presensi Bulanan (`/attendance/recap`), Laporan Tukar Jadwal (`/schedule-swaps`). |
| **Guru Pengajar / Wali Kelas** | `2` | Dashboard KBM harian (`teacher.dashboard`), pengisian jurnal mengajar + foto dokumentasi + sinkronisasi otomatis izin resmi siswa + presensi 5-status (`Hadir`, `Izin`, `Sakit`, `Alpa`, `Terlambat`), Permohonan Tukar Jadwal (`/schedule-swaps`), Jadwal Ruangan (`/room-schedules`), Materi & Ujian (`/exams`). **Jika menjadi Wali Kelas**: Panel Wali Kelas (`Cek List Siswa`, `Presensi Siswa`, `Catatan Siswa`, pantau `Izin Siswa Perwalian`). |
| **Tim Kesiswaan / Guru BK** | `3` | Verifikasi & keputusan permohonan izin siswa (`/leave-requests`: `Approved`, `Rejected`, `Pending`), Rekapitulasi Presensi Bulanan (`/attendance/recap`), Manajemen Pengguna (`/users`), Data Kelas & Simulasi Kenaikan Kelas/Mutasi (`/classes`, `/classes/promotion`), Manajemen Ruangan (`/rooms`), Kalender Akademik & Hari Tidak Efektif (`/academic-calendar`, `/ineffective-days`), dan Jadwal Ruangan. |
| **Siswa / Orang Tua** | `4` | Rekap presensi digital pribadi (`/attendance`), pengajuan permohonan izin/sakit berkategori (`Sakit`, `Dispensasi`, `Izin`, `Lainnya`) dengan unggah dokumen bukti (`/leave-requests/create`), pemantauan status verifikasi izin, dan akses Materi & Ujian (`/exams`). |

---

## 6. Aturan Inti & Peningkatan Terkini

### 6.1. Lima Aturan Inti Spesifikasi UKK (Rules a–e)

1. **Aturan a (Pencegahan Konflik Jadwal)**:
   - Validasi interval `Start_A < End_B AND End_A > Start_B` pada Guru, Ruangan, dan Rombel untuk hari dan siklus putaran yang sama.
   - *Implementasi*: [`app/Services/ScheduleConflictService.php`](app/Services/ScheduleConflictService.php).
2. **Aturan b (Sakelar Putaran Global & Perlindungan Kunci)**:
   - Penggantian siklus aktif (`Putaran 1` / `Putaran 2`) berlaku global ke filter jadwal KBM. Jika jadwal dalam status terkunci (`is_schedule_locked = true`), perubahan putaran juga ditolak di server hingga kunci dibuka.
   - *Implementasi*: [`app/Http/Controllers/SystemConfigurationController.php`](app/Http/Controllers/SystemConfigurationController.php) dan [`app/Http/Controllers/DashboardController.php`](app/Http/Controllers/DashboardController.php).
3. **Aturan c (Penguncian Jadwal Server-Side)**:
   - Saat `is_schedule_locked = true`, seluruh penambahan, pengubahan, penghapusan jadwal, dan pergantian putaran diblokir di server.
   - *Implementasi*: [`app/Http/Middleware/CheckScheduleLock.php`](app/Http/Middleware/CheckScheduleLock.php).
4. **Aturan d (Sinkronisasi Izin ke Jurnal)**:
   - Permohonan izin berstatus `Pending` secara bawaan. Presensi siswa otomatis tersinkronisasi (`Izin` / `Sakit`) saat guru pengajar mengisi jurnal pada hari terkait.
   - *Implementasi*: [`app/Services/JournalService.php`](app/Services/JournalService.php) dan [`app/Services/LeaveRequestService.php`](app/Services/LeaveRequestService.php).
5. **Aturan e (Peringatan Keterlambatan Jurnal)**:
   - Peringatan muncul otomatis di dashboard guru/Waka secara dinamis berdasarkan `end_time` dan batas toleransi tanpa ketergantungan *cron job*.
   - *Implementasi*: [`app/Services/DashboardService.php`](app/Services/DashboardService.php).

### 6.2. Pembaruan Fitur & Antarmuka Terbaru

- **Manajemen Pengguna & Reset Kata Sandi Pilihan** ([`/users`](routes/web.php)): Filter khusus peran `Guru` / `Siswa`, serta opsi reset kata sandi untuk memilih kata sandi sementara acak (`default`) atau memasukkan kata sandi baru secara manual (`custom`, min. 6 karakter), keduanya menandai `must_change_password`.
- **Paginasi Klien Tanpa Inner-Scroll** ([`x-client-pagination`](resources/views/components/client-pagination.blade.php)): Daftar panjang melebihi 15 baris otomatis dipaginasi (dashboard absensi & jurnal tertunda, rekap presensi bulanan per rombel, daftar pengguna, dsb.) sehingga kartu tampil penuh tanpa scrollbar internal.
- **Impor Jadwal Satu Alur** ([`/schedules`](resources/views/schedules/index.blade.php)): Penyederhanaan tombol impor jadwal menjadi satu aksi langsung menuju alur impor & pratinjau.
- **Kenaikan Kelas & Mutasi** ([`/classes/promotion`](routes/web.php)): Simulasi kenaikan rombel, mutasi siswa, dan penugasan Wali Kelas serta Guru BK.
- **Rekapitulasi Presensi Bulanan Mandiri** ([`/attendance/recap`](routes/web.php)): Halaman khusus analisis kehadiran bulanan dan rincian per rombongan belajar.
- **Modern Web & Aksesibilitas**:
  - Layout dashboard Waka seimbang tanpa blank space pada kartu statistik harian dan daftar simetris untuk jurnal tertunda.
  - Native `<details>` pada daftar siswa tidak hadir agar mendukung pencarian halaman (`Ctrl+F`), CSS Grid `subgrid` untuk kartu sejajar, `content-visibility: auto`, serta sinkronisasi `aria-invalid`, `aria-describedby`, dan `#app-announcer` dengan `:user-invalid`.
  - Middleware [`SecurityHeaders`](app/Http/Middleware/SecurityHeaders.php) dan komponen modal konfirmasi aksesibel berbasis `<dialog>`.

---

## 7. Menjalankan Automated Test Suite

Suite pengujian (**381 pengujian lulus, 2.873 assertions** di `tests/Feature/` dan `tests/Unit/`) mencakup otentikasi, rate limiting, otorisasi 5 peran, penjadwalan & konflik, impor data, izin & sinkronisasi jurnal, rekap presensi, header keamanan, dan REST API V1:

```bash
# Menjalankan seluruh test suite
php artisan test

# Menjalankan pengujian fitur tertentu
php artisan test --filter DashboardTest
php artisan test --filter WakaUserManagementFilterTest
php artisan test --filter UserPasswordResetAndExportTest
php artisan test --filter LeaveDecisionAndWaliMenuD5Test
php artisan test --filter SuperadminRoleParityAndTechnicalSeparationTest
```

---

## 8. Panduan Impor Data

Impor berkas menggunakan mekanisme **Pratinjau (Preview) 2 Tahap** sebelum commit ke database:

1. **Impor Pegawai & Guru** (`/waka/import-pegawai`) — Excel/CSV data guru/pegawai dari Dapodik/SIMPEG; menghasilkan akun dan kredensial sementara.
2. **Impor Wali Kelas & Guru BK** (`/waka/import-wali-bk`) — Memetakan penugasan `homeroom_assignments` dan `counselor_assignments`.
3. **Impor Jadwal Pelajaran (FET XML / CSV)** (`/waka/schedules/import`, juga diakses langsung dari `/schedules`) — Mendukung seleksi `Putaran 1` atau `Putaran 2` secara independen beserta deteksi konflik guru, ruang, dan rombel.

---

## 9. Konfigurasi Email, OTP Reset Password & Rate Limiting Auth

- **Prasyarat Reset Password OTP**: Hanya tersedia bagi akun yang sudah memiliki alamat email terverifikasi melalui halaman profil (`/profile`). Akun tanpa email terverifikasi dapat di-reset melalui `/users` oleh Waka/Kesiswaan/Superadmin.
- **Pengaturan SMTP**: Dikonfigurasi khusus **Superadmin** (role `5`) melalui GUI di `/system-configuration/email` atau via `.env`, dilengkapi fitur kirim email uji dan proteksi TLS.
- **Endpoint Autentikasi & Reset Password**:
  - Login API: `POST /api/v1/auth/login` (endpoint legacy `POST /api/login` tetap aktif untuk kompatibilitas).
  - Request OTP: `POST /api/v1/auth/forgot-password` (Web: `/forgot-password`).
  - Submit Reset Password: `POST /api/v1/auth/reset-password` (Web: `/reset-password` & `/reset-password-token`).
- **Pembatasan Laju (*Rate Limiting*)**:
  - Reset password & verifikasi email (`/profile/email/verify`, `/api/v1/auth/email/verify`, `/api/v1/profile/email/verify`): **6 permintaan/menit per IP** (`throttle:6,1`).
  - Login (`/login`, `/api/login`, `/api/v1/auth/login`): **5 percobaan/menit per username + IP** (`throttle:api-login`).

---

## 10. Konfigurasi AI & Background Login (Superadmin)

- **Google Gemini API Key & Pemilihan Model**: Dikonfigurasi oleh **Superadmin** melalui `/waka/settings` (`gemini_api_key`, `gemini_schedule_model`, `gemini_chat_model`). Jangan pernah menyimpan API key di dalam repositori.
- **Background Login 2-Kolom**: Diunggah/direset oleh **Superadmin** melalui `/waka/settings/login-background` (otomatis dikonversi ke WebP) dan disajikan di `/login-background`.

---

## 11. Catatan Keamanan Produksi

- Pastikan `.env` memiliki `APP_DEBUG=false` dan `APP_ENV=production`.
- Berkas CSV kredensial sementara hasil impor atau bulk reset tersimpan di `storage/app/private/credentials/` (`teacher_credentials_*.csv`, `student_credentials_*.csv`, `bulk_reset_teachers_*.csv`).
- Salin berkas kredensial melalui jalur aman (`scp`), bagikan secara personal, lalu bersihkan berkas sensitif di server:
  ```bash
  rm -f storage/app/private/credentials/*.csv
  ```

---

## 12. Akun Terlindung Bootstrap

Daftar akun awal terlindung (`protectedUsernames`) didefinisikan pada [`config/protected_accounts.php`](config/protected_accounts.php) dan [`app/Models/User.php`](app/Models/User.php):
`waka_kurikulum`, `haris-budiawan`, `ryawidyaningtyas`, `tim_kesiswaan`, `17766`. Kredensial akun bootstrap tidak pernah disimpan dalam repositori.
