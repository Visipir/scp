# SCP (School Class Progress) — Sistem Manajemen Pembelajaran & Presensi SMK

Sistem terintegrasi untuk manajemen jadwal pelajaran berputar (Putaran 1 & Putaran 2), presensi KBM harian, jurnal mengajar guru, perizinan siswa, dan rekapitulasi kehadiran berbasis peran untuk sekolah kejuruan (SMK).

---

## 1. Teknologi & Versi (Stack)

- **Backend**: Laravel 11.x (PHP 8.2 / 8.3)
- **Database**: SQLite 3 (Production & Staging teruji) / MySQL 8.0+ didukung
- **Frontend Web**: Blade Templates, Tailwind CSS (Swiss Minimalist Dark Console UI), Alpine.js, Chart.js
- **Asset Bundler**: Vite 5.x
- **Mobile Client**: Native iOS (SwiftUI, MVVM, URLSession, Keychain, QuickLook, iOS 17+)
- **Testing**: PHPUnit 11.x / Laravel Testing Framework

---

## 2. Prasyarat Sistem (Prerequisites)

- **PHP**: `^8.2` dengan ekstensi `pdo_sqlite`, `mbstring`, `openssl`, `curl`, `xml`, `zip`, `gd`, `bcmath`.
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

### 3.5. Pembuatan Akun Waka Kurikulum Pertama (Bootstrap)
Gunakan `RealAccountsSeeder` dengan kredensial yang disuplai via environment variabel tanpa menuliskan kata sandi ke dalam repositori:
```bash
SEED_DEFAULT_PASSWORD="<KATA_SANDI_AMAN_WAKA>" php artisan db:seed --class=RealAccountsSeeder --force
```

### 3.6. Menjalankan Aplikasi
- **Lingkungan Produksi**: Konfigurasikan Nginx menuju direktori `public/`.
- **Lingkungan Uji / Lokal**:
  ```bash
  php artisan serve --host=0.0.0.0 --port=8000
  ```

---

## 4. Struktur Direktori Proyek

```text
/
├── app/
│   ├── Console/Commands/        # Artisan commands (import, reset kredensial, proteksi akun)
│   ├── Http/
│   │   ├── Controllers/         # Web Controllers (Waka, Guru, Wali, Siswa)
│   │   │   └── Api/V1/          # REST API Controllers (Sanctum-authenticated)
│   │   ├── Middleware/          # Role, schedule lock, password change guards
│   │   └── Resources/Api/V1/    # JSON API Transformers (V1 Spec)
│   ├── Models/                  # Eloquent Models (User, Schedule, Journal, LeaveRequest, dsb.)
│   └── Services/                # Business logic (Schedule, Employee, Student, Mail, dsb.)
├── config/                      # Application config (protected_accounts.php, auth, mail)
├── database/
│   ├── migrations/              # Database schema migrations
│   └── seeders/                 # Seeders (RoleSeeder, RealAccountsSeeder, UserSeeder)
├── docs/                        # Dokumentasi teknis, API spec, UKK checklist, runbook
├── public/                      # Entry point (index.php), Vite assets, favicon, login background
├── resources/
│   ├── css/ & js/               # Frontend source (Tailwind CSS Swiss Console, Alpine.js)
│   └── views/                   # Blade templates per peran (waka, classes, schedules, auth)
├── routes/                      # Route definitions (web.php, api.php, auth.php)
├── storage/app/private/         # Direktori privat (backups, credentials CSV, gitignored)
└── tests/                       # Automated test suite (Feature & Unit)
```

---

## 5. Peran Pengguna & Fitur Utama

| Peran | ID | Fitur Utama |
|---|---|---|
| **Waka Kurikulum** | `1` | Manajemen Master Jadwal (Putaran 1 & 2), Kunci Jadwal (`lock/unlock`), Impor Guru/Pegawai, Impor Wali/BK, Impor Jadwal Pelajaran, Manajemen Pengguna & Reset Password Sementara, Pengaturan Email SMTP, Pengaturan Gemini AI, Pengaturan Background Login 2-Kolom. |
| **Guru Pengajar** | `2` | Dashboard KBM harian (banner akhir pekan / jadwal kosong), Pengisian Jurnal Mengajar + Foto + Presensi 5-status (H/S/I/A/D), Permohonan Tukar Jadwal, Akses Siswa & Rekap Kehadiran Kelas yang Diajar. |
| **Wali Kelas / BK** | `2` / `3` | **Wali Kelas**: Monitoring presensi kelas perwalian, verifikasi & persetujuan izin siswa perwalian, ekspor rekap.<br>**Guru BK**: Bimbingan konseling, verifikasi & persetujuan izin seluruh kelas asuhan BK, rekap presensi perwalian BK. |
| **Siswa / Orang Tua**| `4` | Informasi jadwal pelajaran hari ini, kartu presensi digital, pengajuan izin/sakit mandiri dengan unggah surat bukti (PDF/gambar), pemantauan status persetujuan izin real-time. |

---

## 6. Lima Aturan Inti Bisnis (Core Business Rules)

1. **Deteksi Konflik Jadwal (Conflict Detection)**
   - Guru, ruang, dan rombel tidak boleh memiliki jadwal tumpang tindih pada hari, jam (JP), dan putaran siklus yang sama.
   - *Implementasi*: [`app/Services/ScheduleService.php::validateConflict()`](file:///var/www/SCP/app/Services/ScheduleService.php) dan `validateSlotConflict()`.

2. **Penguncian Jadwal Pelajaran (Schedule Lock Enforcement)**
   - Saat status `is_schedule_locked = true` pada `system_configurations`, Guru dilarang menambah, mengubah, atau menghapus jadwal pelajaran (`HTTP 403 Forbidden`). Tombol aksi otomatis disembunyikan.
   - *Implementasi*: [`app/Http/Middleware/EnsureScheduleNotLocked.php`](file:///var/www/SCP/app/Http/Middleware/EnsureScheduleNotLocked.php) dan [`app/Http/Controllers/ScheduleController.php`](file:///var/www/SCP/app/Http/Controllers/ScheduleController.php).

3. **Alur Izin & Peran Ganda Wali / BK (Leave Approval & Dual Role Workflow)**
   - Siswa mengajukan izin -> Wali Kelas dan Guru BK dari kelas siswa dapat menyetujui atau menolak.
   - Keputusan terakhir (*last decision*) tercatat dengan metadata lengkap (`decided_by_user_id`, `decided_by_role`, `decided_at`).
   - Sinkronisasi otomatis ke jurnal presensi: jika disetujui, status absensi siswa sinkron menjadi Sakit/Izin; jika ditolak/pending, kembali ke Alpa.
   - *Implementasi*: [`app/Http/Controllers/LeaveRequestController.php`](file:///var/www/SCP/app/Http/Controllers/LeaveRequestController.php), [`app/Models/LeaveRequest.php::recordDecision()`](file:///var/www/SCP/app/Models/LeaveRequest.php), [`app/Http/Controllers/Api/V1/LeaveRequestController.php`](file:///var/www/SCP/app/Http/Controllers/Api/V1/LeaveRequestController.php).

4. **Cakupan Akses Kelas Guru Pengajar (Teaching Class Access Scoping)**
   - Guru pengajar otomatis berhak melihat daftar siswa dan rekap kehadiran pada kelas-kelas yang tercantum di jadwal mengajarnya (`schedules`), namun dibatasi tidak dapat mengubah data siswa atau mengakses catatan kelas lain.
   - *Implementasi*: [`app/Models/User.php::authorizedClassIds()`](file:///var/www/SCP/app/Models/User.php), `canAccessClass()`, [`app/Http/Controllers/ClassController.php`](file:///var/www/SCP/app/Http/Controllers/ClassController.php), [`app/Http/Controllers/ReportController.php`](file:///var/www/SCP/app/Http/Controllers/ReportController.php).

5. **Kekebalan Akun Terlindung Penguji (Protected Accounts Immunity)**
   - 5 Akun penguji (`waka_kurikulum`, `haris-budiawan`, `ryawidyaningtyas`, `tim_kesiswaan`, `17766`) terlindung penuh dari reset massal, penimpaan nama/username, perubahan role, dan purge dummy.
   - *Implementasi*: [`config/protected_accounts.php`](file:///var/www/SCP/config/protected_accounts.php), [`app/Models/User.php::isProtectedAccount()`](file:///var/www/SCP/app/Models/User.php), [`app/Services/UserService.php`](file:///var/www/SCP/app/Services/UserService.php), [`app/Services/EmployeeImportService.php`](file:///var/www/SCP/app/Services/EmployeeImportService.php), [`app/Services/StudentImportService.php`](file:///var/www/SCP/app/Services/StudentImportService.php).

---

## 7. Menjalankan Automated Test Suite

Aplikasi dilengkapi suite pengujian otomatis (324+ skenario) yang mencakup otentikasi, otorisasi RBAC, penjadwalan, impor, izin, dan kompatibilitas API V1.

```bash
# Menjalankan seluruh test suite
php artisan test

# Menjalankan pengujian fitur tertentu
php artisan test --filter ProtectedAccountsF1Test
php artisan test --filter LeaveDecisionAndWaliMenuD5Test
php artisan test --filter ScheduleConflictTest
```

---

## 8. Panduan Impor Data (Waka Kurikulum)

Impor berkas dilakukan melalui antarmuka web Waka Kurikulum dengan mekanisme **Pratinjau (Preview) 2 Tahap**:

1. **Impor Pegawai & Guru** (`/waka/import-pegawai`)
   - **Format**: File Excel/CSV daftar guru dari Dapodik/SIMPEG.
   - Kolom: NIP, Nama Lengkap, Jenis Kelamin, Pangkat/Golongan, Jabatan.
   - Menghasilkan akun guru dengan username standar dan password sementara.

2. **Impor Wali Kelas & Guru BK** (`/waka/import-wali-bk`)
   - **Format**: File mapping kelas, nama guru, dan peran penugasan (Wali Kelas / BK).
   - Memperbarui tabel `homeroom_assignments` dan `counselor_assignments`.

3. **Impor Jadwal Pelajaran** (`/waka/schedules/import`)
   - **Format**: Format matriks tabular atau jadwal per kelas (Senin s.d. Jumat, JP 1–10).
   - Melakukan validasi otomatis terhadap konflik guru ganda, ruang ganda, dan rombel ganda sebelum commit.

---

## 9. Konfigurasi Email & OTP Reset Password

- Pengaturan SMTP dapat dikonfigurasi melalui GUI Waka Kurikulum di `/settings/email` atau via `.env`.
- Menggunakan skema TLS teruji dengan dukungan fallback log mailer.
- Endpoint reset password dengan kode OTP 6-digit terenkripsi:
  - Request Code: `POST /api/v1/forgot-password`
  - Submit Code: `POST /api/v1/reset-password`

---

## 10. Konfigurasi AI & Background Login

- **Google Gemini API Key**: Dikonfigurasi secara aman melalui menu **Pengaturan Waka** (`/waka/settings`). Jangan pernah melakukan commit kunci API ke repositori.
- **Background Login 2-Kolom**: Waka dapat mengunggah gambar latar login instansi melalui `/waka/settings` (otomatis dikonversi ke WebP teroptimasi). Dilayani via endpoint publik `/login-background`.

---

## 11. Catatan Keamanan Produksi

- Pastikan `.env` memiliki `APP_DEBUG=false` dan `APP_ENV=production`.
- Berkas CSV kredensial sementara hasil impor tersimpan di `storage/app/private/credentials/`.
- Unduh berkas kredensial melalui jalur aman (`scp`), bagikan secara personal kepada guru/wali, dan segera hapus arsip CSV dari server setelah didistribusikan.
