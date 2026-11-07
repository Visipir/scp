# Runbook Deploy Produksi (LIVE) — SCP

> **PERINGATAN**: Dokumen ini adalah draf runbook operasional. **JANGAN** dieksekusi otomatis oleh agen tanpa instruksi eksplisit pada setiap langkah.
> Langkah yang ditandai **`[BUTUH DATA DARI OPERATOR]`** memerlukan input atau file dari Anda sebelum dijalankan.

---

## 1. Backup Database LIVE & Verifikasi File

```bash
cd /var/www/SCP
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
mkdir -p storage/app/private/backups

# 1a. Backup SQLite Live
cp database/database.sqlite "storage/app/private/backups/database_live_${TIMESTAMP}.sqlite"
ls -lh "storage/app/private/backups/database_live_${TIMESTAMP}.sqlite"
sqlite3 "storage/app/private/backups/database_live_${TIMESTAMP}.sqlite" "PRAGMA integrity_check;"
sqlite3 "storage/app/private/backups/database_live_${TIMESTAMP}.sqlite" \
  "SELECT 'users', COUNT(*) FROM users UNION ALL SELECT 'classes', COUNT(*) FROM classes UNION ALL SELECT 'schedules', COUNT(*) FROM schedules;"

# 1b. Backup MySQL Live (jika env menggunakan MySQL)
# [BUTUH DATA DARI OPERATOR]: Kredensial MySQL produksi bila aktif
# mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "storage/app/private/backups/mysql_live_${TIMESTAMP}.sql"
```

- **Kriteria Lulus**: `integrity_check = ok`, ukuran file backup `> 0`, row count awal tercatat (`users=1758, classes=48, schedules=124`).

### 1c. Verifikasi Awal 5 Akun Terlindung Penguji (`AKUN TERLINDUNG`)

Pastikan 5 akun penguji (`waka_kurikulum`, `haris-budiawan`, `ryawidyaningtyas`, `tim_kesiswaan`, `17766`) tercatat fingerprint `sha256(password)`, `username`, `fullname`, `role_id`, dan `must_change_password=false` sebelum langkah deploy:

```bash
cd /var/www/SCP
php artisan accounts:protect-credentials --dry-run
```

---

## 2. Aktifkan Mode Maintenance

```bash
cd /var/www/SCP
php artisan down --render="errors::503" --retry=60
```

---

## 3. Update Kode & Konfigurasi Environment Produksi

- **`[BUTUH DATA DARI OPERATOR]`**:
  - Konfigurasi SMTP produksi (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`) untuk pengiriman OTP verifikasi email / lupa password.
  - Nilai `SEED_DEFAULT_PASSWORD` untuk akun Waka / Tim Kesiswaan di `storage/app/seed/accounts.csv`.
  - Daftar akun Waka & Tim Kesiswaan nyata untuk `storage/app/seed/accounts.csv`.
  - Nilai `DEMO_ACCOUNTS_PASSWORD` (atau CSV kredensial demo) untuk akun demo penguji.

```bash
cd /var/www/SCP
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Pastikan variabel .env produksi:
# APP_ENV=production
# APP_DEBUG=false
# SEED_DEFAULT_PASSWORD="<DARI_OPERATOR>"
```

---

## 4. Jalankan Migrasi Database

```bash
cd /var/www/SCP
php artisan migrate --force
```

- Migrasi mencakup:
  - `homeroom_assignments` & `counselor_assignments`
  - `user_name_aliases`
  - `decided_by_user_id`, `decided_by_role`, `decided_at` pada `leave_requests`
  - `leave_request_id` pada `attendances` & tabel `leave_request_decisions`

---

## 5. Pembersihan Data Dummy & Jurnal Uji di LIVE

- **`[BUTUH DATA DARI OPERATOR]`**: Konfirmasi daftar ID `journals` / `attendances` uji di database LIVE sebelum penghapusan dijalankan.

```bash
cd /var/www/SCP

# 5a. Preview daftar jurnal & akun dummy di LIVE (Dry-Run)
php artisan accounts:purge-dummy --dry-run

# 5b. Hapus attendances + journals milik 13 jadwal uji (Schedule ID: 4, 8, 9, 10, 11, 14, 15, 16, 17, 25, 29, 44, 62)
# serta akun dummy guru_andi (ID 2) dan ortu_dina (ID 6), lalu jalankan:
php artisan accounts:purge-dummy --force

# 5c. Verifikasi ulang target dummy = 0
php artisan accounts:purge-dummy --dry-run
```

---

## 6. Import Data berurutan via UI Waka Kurikulum

- **`[BUTUH DATA DARI OPERATOR]`**: Pastikan file final yang diunggah adalah versi resmi terbaru:
  1. **Tahap 1 — Import Pegawai (`/waka/import-pegawai`)**:
     - Unggah `Data Pegawai 2026 Per Juni 2026 FIX.xlsx`.
     - Pada tabel konfirmasi Fuzzy, pilih **MERGE** untuk 5 pasangan:
       - `TRI SUSILAWATI` -> User ID `1757` (`TRI SUSILOWATI`)
       - `ANASTASIA RATNA SULISTIYANI` -> User ID `1732` (`ANASTYA RATNA S., S.Pd`)
       - `AHMAD MUHLASHIN` -> User ID `1760` (`AHMAD MUHLASIN`)
       - `YULITA EKAWINAJENG` -> User ID `1719` (`YULITA EKA WINAJENG, S.Pd`)
       - `MUHAMAD HERU PURNOMO` -> User ID `1753` (`HERU PURNOMO`)
     - Klik **Commit Import Pegawai** (menyimpan nama resmi Pegawai + mencatat nama lama ke `user_name_aliases`).
  2. **Tahap 2 — Import Wali Kelas & Guru BK (`/waka/import-wali-bk`)**:
     - Unggah `Wali Kelas dan Guru BK 2026-2027.xlsx`.
     - Pastikan seluruh 48 kelas memiliki 1 Wali Kelas (`homeroom_assignments = 48`) dan 5 Guru BK mencakup 48 kelas (`counselor_assignments = 48`).
     - Klik **Commit Import Wali & BK**.
  3. **Tahap 3 — Import Jadwal PDF Putaran 2 (`/waka/schedules/import`)**:
     - Unggah `JADWAL GURU PUT 2 SEM GANJIL 26_27 REVISI 2.pdf` dengan target `Putaran 2`.
     - Pastikan preview menampilkan `866` blok valid (`1.453 JP`), `unmapped = 0`, `conflicts = 0`.
     - Klik **Commit Import Jadwal**.

---

## 7. Reset Password Guru & Siswa (+ Ekspor CSV Kredensial)

```bash
cd /var/www/SCP

# 7a. Reset password sementara seluruh guru via UI Waka (/users -> Reset Password Massal Guru)
#     atau via service, menghasilkan CSV di storage/app/private/credentials/bulk_reset_teachers_*.csv

# 7b. Dry-run reset password siswa hasil import (memastikan siswa yang sudah ganti password di-skip)
php artisan students:reset-imported-passwords --dry-run

# 7c. Apply reset password siswa hasil import -> ekspor CSV ke storage/app/private/credentials/
php artisan students:reset-imported-passwords --apply
```

- Wali Kelas dan Waka juga dapat mengunduh **Kartu Login (CSV)** per kelas melalui menu `Data Kelas -> Pilih Kelas -> Kartu Login (CSV)` (`/classes/{class}/login-cards`).

---

## 8. Penambahan Akun Tambahan & Verifikasi Akun Terlindung Penguji

> **PENTING**: 5 akun terlindung penguji (`waka_kurikulum`, `haris-budiawan`, `ryawidyaningtyas`, `tim_kesiswaan`, `17766`) **TIDAK BOLEH** dinonaktifkan, diganti username/nama/role-nya, atau di-reset password-nya karena kredensialnya telah dikirim ke penguji.

- **`[BUTUH DATA DARI OPERATOR]`**:
  - Daftar akun Waka / Tim Kesiswaan tambahan (jika ada) di `storage/app/seed/accounts.csv`.
  - Password akun demo tambahan (`DEMO_ACCOUNTS_PASSWORD` di `.env`) bila diperlukan selain 5 akun terlindung.

```bash
cd /var/www/SCP

# 8a. (Opsional) Seed akun Waka & Tim Kesiswaan tambahan dari storage/app/seed/accounts.csv
#     Akun terlindung otomatis di-skip oleh RealAccountsSeeder.
php artisan db:seed --class=RealAccountsSeeder --force

# 8b. Verifikasi kembali fingerprint (sha256 password, username, nama, role, must_change_password=false)
#     dari 5 akun terlindung penguji setelah seluruh tahapan import & reset selesai:
php artisan accounts:protect-credentials --dry-run
```

---

## 9. Matikan Mode Maintenance & Jalankan Smoke Test

```bash
cd /var/www/SCP
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

### Checklist Smoke Test Produksi:
1. Login sebagai **Waka Kurikulum** -> cek Dashboard, Jadwal (`866` blok), Data Kelas (`48` kelas).
2. Login sebagai **Guru Baru** (menggunakan password sementara dari CSV) -> wajib diarahkan ke halaman ganti password (`must_change_password = true`).
3. Login sebagai **Guru Pengajar Biasa** -> buka form Jurnal KBM, siswa muncul, simpan jurnal & presensi, buka `Rekap Presensi Kelas` yang diajar (`200 OK`), coba akses kelas tidak diajar (`403`), coba akses izin siswa (`403`).
4. Login sebagai **Guru Wali Kelas** -> buka menu Wali Kelas (`Cek List Siswa`, `Presensi & Catatan Siswa`, `Izin Siswa Perwalian`, `Kartu Login (CSV)`), coba unduh kartu login kelas lain (`403`).
5. Login sebagai **Guru BK** -> verifikasi hanya melihat kelas binaan dan izin siswa kelas binaan.
6. Login sebagai **Siswa** -> cek jadwal kelas, rekap kehadiran pribadi, dan status pengajuan izin (`Disetujui oleh BK/Wali: <nama>`).

---

## 10. Prosedur Rollback (Restore Backup)

Jika ditemukan kegagalan kritis saat deploy atau smoke test:

```bash
cd /var/www/SCP
php artisan down

# Restore SQLite backup
cp "storage/app/private/backups/database_live_${TIMESTAMP}.sqlite" database/database.sqlite
chown www-data:www-data database/database.sqlite

# (Jika MySQL)
# mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "storage/app/private/backups/mysql_live_${TIMESTAMP}.sql"

# Kembalikan revisi git sebelumnya
git reset --hard ORIG_HEAD
php artisan optimize:clear
php artisan up
```
