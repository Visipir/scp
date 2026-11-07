# Runbook Promosi Database Kandidat (`database_candidate.sqlite` -> `database.sqlite`)

Dokumen ini memandu promosi atomik dari `database/database_candidate.sqlite` (yang sudah berisi hasil import E6/F4: Pegawai + Wali/BK + Jadwal Putaran 2, serta hash asli 5 akun terlindung) menjadi `database/database.sqlite` dan mengembalikan `.env` ke `DB_DATABASE=/var/www/SCP/database/database.sqlite`.

> **PERINGATAN OPERATOR**: Jangan jalankan skrip promosi sebelum keputusan operator diberikan pada langkah-langkah bertanda **`[KEPUTUSAN OPERATOR]`**.

---

## Ringkasan Status Pra-Promosi (Hasil H2 & H3)

| Parameter | `database.sqlite` (Asli) | `database_staging.sqlite` (Saat Ini Disajikan) | `database_candidate.sqlite` (Kandidat Final) |
| :--- | :--- | :--- | :--- |
| `users` | `1758` | `1756` | `1805` |
| `classes` | `48` | `48` | `48` |
| `schedules` | `124` | `123` | `866` (`1.453 JP`) |
| `homeroom_assignments` | `0` (kolom lama `users.class_id`) | `0` | `48` (1 wali per kelas) |
| `counselor_assignments` | `0` (tabel belum ada) | `0` | `48` (5 guru BK, `tim_kesiswaan` = 0) |
| `user_name_aliases` | `0` (tabel belum ada) | `0` | `45` |
| Migrasi Pending | `7` pending | `0` pending | `0` pending |
| `sha256` File | `3f95a97244fde08a2edfde798822dcbd618b117f36d8d510b92790c1cc7dfbbd` | `26d2b4d56e91744fb912fb3ac55b35969e2c3a9688b692f814d3275df018b78d` | `69babead4ca87064f01a431ef7b15c3bae7f391ecc7006eae423850637645b99` |

### Fingerprint `sha256(password)` 5 Akun Terlindung (Identik di Ketiga DB)

| Username | User ID | Role ID | Nama Lengkap | `must_change_password` | `sha256(password)` |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `waka_kurikulum` | `1` | `1` | `waka_kurikulum` | `false` | `81715802dc3ec65c0e49349ff6919689b95a9a25df89d0867019c61c126c3717` |
| `haris-budiawan` | `1723` | `2` | `HARIS BUDIAWAN, S.Pd` | `false` | `a6cd61eb3885e8880397bdd492b65560dd63185cdd55381fa1a41d910ee8eb3c` |
| `ryawidyaningtyas` | `1721` | `2` | `RYAWIDYANINGTYAS, S.Pd` | `false` | `a6cd61eb3885e8880397bdd492b65560dd63185cdd55381fa1a41d910ee8eb3c` |
| `tim_kesiswaan` | `4` | `3` | `tim_kesiswaan` | `false` | `f959eb759c01242eda29e695bf5b6c544ee0f6f918a9588517c3f21e1f5e0fc0` |
| `17766` | `1164` | `4` | `17766` | `false` | `d28139182f0aba709c46b6556e146ff57ac1d650ca1113f7b2febacc9249e572` |

---

## Langkah yang Membutuhkan Input / Keputusan Operator

1. **`[KEPUTUSAN OPERATOR 1]` — Waktu Maintenance Window**:
   - Promosi menjalankan `php artisan down` selama beberapa detik. Pastikan tidak ada pengguna aktif yang sedang mengunggah jurnal/izin saat promosi berlangsung.
2. **`[KEPUTUSAN OPERATOR 2]` — Selisih Data Aktivitas Antara `database.sqlite` Asli dan `database_staging.sqlite`**:
   - Karena `.env` disajikan dari `database_staging.sqlite` sejak `2026-11-06 04:05:25 UTC`, pastikan apakah ada data transaksi di `database.sqlite` asli (mis. 2 akun dummy yang dihapus di staging: `1758` vs `1756`, dan 1 jadwal dummy: `124` vs `123`) yang memang sengaja dibersihkan pada tahap A1/E6.
3. **`[KEPUTUSAN OPERATOR 3]` — Konfirmasi File Kredensial Verifikasi**:
   - Pastikan `storage/app/private/protected-verify.env` tetap tersedia sebelum menjalankan skrip karena langkah verifikasi pasca-promosi membaca password dari file tersebut tanpa mencetaknya.
4. **`[KEPUTUSAN OPERATOR 4]` — Pengaturan `APP_ENV` dan `APP_DEBUG` di `.env` (Opsional)**:
   - Saat ini `.env` memiliki `APP_ENV=local` dan `APP_DEBUG=true`. Skrip promosi **hanya** mengubah baris `DB_DATABASE=/var/www/SCP/database/database.sqlite`. Jika operator ingin mengubah `APP_ENV=production` dan `APP_DEBUG=false`, lakukan secara terpisah atas persetujuan operator.

---

## Urutan Langkah Promosi (`scripts/promote-candidate.sh`)

1. **Verifikasi Prasyarat (Pre-Flight sebelum `artisan down`)**:
   - Pastikan file `database/database_candidate.sqlite`, `database/database.sqlite`, `database/database_staging.sqlite`, `storage/app/private/backups/database_live_h0.sqlite`, dan `storage/app/private/protected-verify.env` ada.
   - Verifikasi `sha256` dari `database/database_candidate.sqlite` sama dengan `69babead4ca87064f01a431ef7b15c3bae7f391ecc7006eae423850637645b99` (atau nilai `--expected-sha256=<hash>`).
   - Verifikasi `APP_DEBUG=false` di `.env`.
   - Verifikasi otomatis 5 hash password akun terlindung di `database_candidate.sqlite` sama persis dengan `database_live_h0.sqlite`.
   - Verifikasi `migrate:status` pada `database_candidate.sqlite` tidak memiliki migrasi `Pending`.
   - Uji kering tanpa menulis apa pun dapat dijalankan dengan:
     ```bash
     bash scripts/promote-candidate.sh --dry-run
     ```
2. **Aktifkan Maintenance Mode**:
   ```bash
   php artisan down --retry=15
   ```
3. **Backup Berkas dengan `sha256` dan Ukuran**:
   - Simpan backup bertanda waktu (`TIMESTAMP`) ke `storage/app/private/backups/promote_<TIMESTAMP>/`:
     - `.env`
     - `database/database.sqlite`
     - `database/database_staging.sqlite`
     - `database/database_candidate.sqlite`
   - Catat `sha256sum` dan `ls -lh` ke `manifest.txt`.
4. **Salin Kandidat ke `database.sqlite`**:
   ```bash
   cp -p database/database_candidate.sqlite database/database.sqlite
   chown www-data:www-data database/database.sqlite
   chmod 664 database/database.sqlite
   ```
5. **Verifikasi Status Migrasi (`No Pending`)**:
   ```bash
   DB_DATABASE=/var/www/SCP/database/database.sqlite php artisan migrate:status
   ```
   Pastikan tidak ada baris `Pending`.
6. **Ubah `.env` ke `database.sqlite` & Bersihkan Cache**:
   ```bash
   sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/www/SCP/database/database.sqlite|' .env
   php artisan config:clear
   php artisan cache:clear
   chown -R www-data:www-data storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
   ```
7. **Nonaktifkan Maintenance Mode**:
   ```bash
   php artisan up
   ```
8. **Verifikasi Fingerprint & Login Nyata HTTP 5 Akun Terlindung**:
   - Bandingkan `sha256(password)`, `username`, `fullname`, `role_id`, dan `must_change_password=0` kelima akun terlindung di `database.sqlite`.
   - Uji login HTTP nyata (Web `POST http://127.0.0.1:85/login` -> `302 /dashboard` -> `200`, serta API `POST http://127.0.0.1:85/api/v1/auth/login` -> `200` + `GET /api/v1/auth/me` -> `200`) untuk kelima akun memakai `storage/app/private/protected-verify.env`.

---

## Prosedur Rollback (Jika Terjadi Kegagalan)

Jika salah satu pemeriksaan pasca-promosi gagal, jalankan langkah rollback berikut:

```bash
cd /var/www/SCP
php artisan down

# Pilih direktori backup promosi terakhir
BACKUP_DIR=$(ls -dt storage/app/private/backups/promote_* | head -n 1)

# Pulihkan file database.sqlite, database_staging.sqlite, dan .env
cp -p "$BACKUP_DIR/database.sqlite.bak" database/database.sqlite
cp -p "$BACKUP_DIR/database_staging.sqlite.bak" database/database_staging.sqlite
cp -p "$BACKUP_DIR/.env.bak" .env

chown www-data:www-data database/database.sqlite database/database_staging.sqlite
php artisan config:clear
php artisan cache:clear
chown -R www-data:www-data storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

php artisan up
sha256sum database/database.sqlite database/database_staging.sqlite .env
```
