# Prosedur Pembersihan Repositori Live (`/var/www/SCP/.git`)

> [!CAUTION]
> **DOKUMEN RENCANA EKSEKUSI — JANGAN DIJALANKAN TANPA KONFIRMASI EKSPLISIT.**
> Prosedur ini dirancang untuk membersihkan riwayat lama dari direktori `.git` di `/var/www/SCP` setelah verifikasi dry-run pada `/tmp/live-git-copy` berhasil 100%.

---

## 1. Pemeriksaan Prasyarat & Penjamin Keamanan (Prerequisites)

1. **Jaminan File Working Tree Tidak Tersentuh**:
   - Seluruh operasi git menggunakan flag `--mixed` (BUKAN `--hard`). Tidak ada file di direktori kerja `/var/www/SCP` yang akan ditimpa atau dihapus oleh git.
2. **Ketersediaan Cadangan Rollback**:
   - Berkas bundle cadangan lengkap riwayat sebelum rewrite tersedia di:
     - `/root/backup/scp-pre-rewrite.bundle`
     - SHA-256: `7849e89fb2e12e75e927ddb0fa8aa192f1cfc0df2655cda8dc5ef225b6823ca2` (atau sesuai verifikasi K1).
3. **Status Aplikasi Live**:
   - Nginx dan PHP-FPM tetap melayani trafik normal dari `/var/www/SCP/public`.

---

## 2. Perintah Eksekusi Riil (Real Run Commands)

Jalankan perintah berikut di terminal server:

```bash
# Masuk ke direktori repositori live
cd /var/www/SCP

# 1. Tambahkan clone bersih sebagai remote lokal (jalur lokal tanpa jaringan)
git remote add clean /root/scp-clean

# 2. Ambil objek git bersih dari repositori bersih
git fetch clean

# 3. Sinkronkan branch feat/dark-console-ui dengan riwayat bersih (mixed reset: aman, working tree utuh)
git reset --mixed clean/feat/dark-console-ui

# 4. Perbarui referensi branch main dan tag pre-restyle ke komit bersih
git branch -f main clean/main
git tag -f pre-restyle clean/main

# 5. Lepaskan remote upstream lama dan remote clean sementara
git remote rm origin
git remote rm clean

# 6. Hapus riwayat reflog dan lakukan pemangkasan penuh (prune) objek lama
git reflog expire --expire=now --all
git gc --prune=now
```

---

## 3. Verifikasi Keberhasilan Pasca-Pembersihan

Setelah perintah di atas selesai, jalankan tiga perintah pembuktian berikut:

```bash
# 1. Buktikan blob lama database_dump.sql benar-benar telah hilang dari database objek git
git cat-file -e 433dc1164c62998d4e863b31563b7c9e7ac5e599
# Output HARUS KODE KELUAR 1 / fatal: Not a valid object name

# 2. Buktikan commit live lama 7ee509f telah hilang
git cat-file -e 7ee509f
# Output HARUS KODE KELUAR 128 / fatal: Not a valid object name

# 3. Konfirmasi total commit sama persis dengan /root/scp-clean
git rev-list --all --count
# Output: 105

# 4. Periksa status working tree
git status --short
# Output yang diharapkan hanya menampilkan berkas untracked fisik (.env.testing, database/database_dump.sql, sqlite_to_mysql.sql)
# dan perbedaan dokumentasi/README dari tree bersih.
```

---

## 4. Prosedur Pemulihan (Rollback Procedure)

Jika terjadi kendala teknis atau inkonsistensi tak terduga selama pembersihan:
```bash
# 1. Pulihkan .git dari bundle cadangan di /root/backup
cd /var/www/SCP
rm -rf .git
git clone /root/backup/scp-pre-rewrite.bundle .git_temp --bare
mv .git_temp .git
git config core.bare false

# 2. Kembalikan HEAD ke feat/dark-console-ui
git checkout feat/dark-console-ui
```
Aplikasi live akan kembali ke status riwayat semula secara utuh.
