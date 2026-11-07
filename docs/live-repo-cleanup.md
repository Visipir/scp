# Prosedur Pembersihan Repositori Live (`/var/www/SCP/.git`) — Versi Aman

> [!CAUTION]
> **DOKUMEN RENCANA EKSEKUSI — JANGAN DIJALANKAN TANPA KONFIRMASI EKSPLISIT.**
> Prosedur ini dirancang untuk mengganti riwayat `.git` di `/var/www/SCP` dengan riwayat bersih dari `/root/scp-clean`.
> Gating: HANYA boleh dijalankan jika berkas `/root/backup/ALLOW_LIVE_CLEANUP` dibuat oleh operator.

---

## 1. Prinsip Keamanan & Preservasi
1. **Working Tree Tetap Aman**:
   - Seluruh operasi menggunakan `git reset --mixed` (BUKAN `--hard`). Berkas aplikasi live tidak akan ditimpa atau dihapus paksa.
2. **Preservasi Ownership & Permissions (Tanpa chmod -R 755)**:
   - Direktori `.git` dipindahkan/disalin menggunakan `cp -a` untuk mempertahankan kepemilikan (`www-data:www-data` atau `root:root`) dan permission asli.
   - Verifikasi permission dilakukan dengan `stat` sebelum dan sesudah swap.
3. **Pembaruan Berkas Terpilih (Allowlist Only)**:
   - Hanya berkas dalam kategori aman (dokumentasi, panduan, unit/feature test, scripts) yang diperbarui menggunakan `git checkout -- <explicit list>`.
   - Berkas kode aplikasi (`app/`, `routes/`, `config/`, `database/`) TIDAK disentuh.
4. **Cadangan Lengkap**:
   - Direktori `.git` asli disalin ke `/root/backup/live-git-old` sebelum modifikasi apa pun.
   - Berkas bundle rollback tersedia di `/root/backup/scp-pre-rewrite2.bundle`.

---

## 2. Perintah Eksekusi Riil (Swap .git + Mixed Reset)

Jalankan perintah berikut di terminal server:

```bash
# 0. Verifikasi Gate File
if [ ! -f /root/backup/ALLOW_LIVE_CLEANUP ]; then
    echo "ERROR: Gate file /root/backup/ALLOW_LIVE_CLEANUP tidak ditemukan. Abort."
    exit 1
fi

cd /var/www/SCP

# 1. Catat stat & ownership .git asli
STAT_BEFORE=$(stat -c '%u:%g %a' /var/www/SCP/.git)
echo "Stat .git asli: $STAT_BEFORE"

# 2. Backup direktori .git live asli
cp -a /var/www/SCP/.git /root/backup/live-git-old
echo "Backup .git live selesai di /root/backup/live-git-old"

# 3. Salin .git bersih dari /root/scp-clean ke lokasi sementara
cp -a /root/scp-clean/.git /var/www/SCP/.git_new

# 4. Samakan kepemilikan .git_new dengan .git asli
chown -R "$(stat -c '%U:%G' /var/www/SCP/.git)" /var/www/SCP/.git_new

# 5. Swap direktori .git
mv /var/www/SCP/.git /var/www/SCP/.git_bak
mv /var/www/SCP/.git_new /var/www/SCP/.git

# 6. Verifikasi stat & ownership setelah swap (harus identik)
STAT_AFTER=$(stat -c '%u:%g %a' /var/www/SCP/.git)
echo "Stat .git baru: $STAT_AFTER"
if [ "$STAT_BEFORE" != "$STAT_AFTER" ]; then
    echo "PERINGATAN: Perbedaan stat terdeteksi! Mengembalikan kepemilikan..."
    chown -R "$(stat -c '%U:%G' /var/www/SCP/.git_bak)" /var/www/SCP/.git
fi

# 7. Mixed reset ke branch feat/dark-console-ui (working tree TIDAK disentuh)
git reset --mixed feat/dark-console-ui

# 8. Periksa status working tree
git status --short

# 9. Checkout HANYA berkas kategori aman (docs, tests, scripts, config test)
git checkout -- \
    .gitignore \
    README.md \
    SCP_CONTEXT.md \
    docs/checklist-ukk.md \
    docs/deploy-runbook.md \
    docs/live-repo-cleanup.md \
    docs/openapi.yaml \
    docs/promote-candidate.md \
    docs/push-after-rewrite.md \
    phpunit.xml \
    scripts/postpush-verify.sh \
    scripts/prepush-check.sh \
    tests/Feature/Api/V1/JournalApiTest.php \
    tests/Feature/FetScheduleImportTest.php \
    tests/Feature/LoginValidationJ1Test.php \
    tests/Feature/ProtectedAccountsF1Test.php \
    tests/Feature/ProtectedAccountsScopeAfterImportF3Test.php \
    tests/Feature/ProtectedCredentialsLoginF2Test.php \
    tests/Feature/StudentLoginCardsAndPasswordResetE4Test.php \
    tests/Feature/UserPasswordResetAndExportTest.php \
    tests/Feature/WeekendEmptyScheduleJ2Test.php \
    tests/Fixtures/synthetic_fet_timetable.csv \
    tests/Fixtures/synthetic_fet_timetable.xml \
    tests/Unit/Services/AiChatbotToolsTest.php

# Catatan: database/seeders/UserSeeder.php sengaja TIDAK di-checkout agar kode seeder live tidak terganggu tanpa approval.
```

---

## 3. Verifikasi Keberhasilan Pasca-Pembersihan

```bash
cd /var/www/SCP

# 1. Buktikan blob dump lama sudah tidak ada
git cat-file -e 433dc1164c62998d4e863b31563b7c9e7ac5e599 2>/dev/null && echo "FAIL" || echo "PASS (blob lama lenyap)"

# 2. Buktikan commit live lama 7ee509f sudah tidak ada
git cat-file -e 7ee509f 2>/dev/null && echo "FAIL" || echo "PASS (commit lama lenyap)"

# 3. Konfirmasi total commit sama dengan repo bersih
echo "Total commit live: $(git rev-list --all --count)"
# Harus cocok dengan /root/scp-clean (114 commit)

# 4. Jalankan prepush safety check di repo live
bash scripts/prepush-check.sh
```

---

## 4. Prosedur Pemulihan (Rollback Procedure)

Jika terjadi anomali atau kesalahan pasca-swap:

```bash
cd /var/www/SCP

# 1. Kembalikan .git asli dari backup live-git-bak
mv /var/www/SCP/.git /var/www/SCP/.git_failed
mv /var/www/SCP/.git_bak /var/www/SCP/.git

# 2. Reset mixed ke commit live semula
git reset --mixed 7ee509f6cb5d6e951966b3f4d6ed443b6aba3435

# 3. Pulihkan berkas docs/tests yang sempat di-checkout
git checkout -- .

# 4. Bersihkan .git_failed jika verifikasi rollback selesai
rm -rf /var/www/SCP/.git_failed
```
Aplikasi live kembali 100% ke kondisi semula tanpa downtime atau perubahan state.
