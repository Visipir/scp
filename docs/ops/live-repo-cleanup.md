# Prosedur Pembersihan Repositori Live (`/var/www/SCP/.git`) — Versi Aman

> [!CAUTION]
> **DOKUMEN RENCANA EKSEKUSI — JANGAN DIJALANKAN TANPA KONFIRMASI EKSPLISIT.**
> Prosedur ini dirancang dalam 2 tahap berpagar (*two-stage gated process*) untuk memastikan keamanan penuh pada instance produksi:
> 1. **Langkah 0 (Patch Routes Live)**: Gated oleh `/root/backup/ALLOW_LIVE_PATCH`.
> 2. **Langkah 1 (Swap Riwayat .git Live)**: Gated oleh `/root/backup/ALLOW_LIVE_CLEANUP`.
>
> Kedua berkas gerbang (*gate files*) saat ini **TIDAK ADA**. Dokumen ini adalah panduan operasional mandiri (*runbook*) untuk operator.

---

## Status Kesiapan Gerbang (*Gate Readiness*)

| Gerbang Eksekusi | Berkas Gerbang | Status Saat Ini | Alasan / Prasyarat |
|---|---|---|---|
| **Langkah 0** (Patch Aplikasi Terpadu) | `/root/backup/ALLOW_LIVE_PATCH` | **NOT READY** | Berkas gerbang belum dibuat oleh operator; patch `/root/backup/live-patch-app.patch` harus ditinjau terlebih dahulu. Menjadi prasyarat mutlak Langkah 1. |
| **Langkah 1** (Swap Riwayat) | `/root/backup/ALLOW_LIVE_CLEANUP` | **NOT READY** | Berkas gerbang belum dibuat oleh operator; mensyaratkan Langkah 0 selesai dan tervalidasi penuh terlebih dahulu. Jika Langkah 0 dilewati, Langkah 1 WAJIB dilewati. |

---

## 1. Prinsip Keamanan & Preservasi
1. **Working Tree Tetap Aman**:
   - Seluruh operasi menggunakan `git reset --mixed` (BUKAN `--hard`). Berkas aplikasi live tidak akan ditimpa atau dihapus paksa.
2. **Preservasi Ownership & Permissions (Tanpa chmod -R 755)**:
   - Direktori `.git` dipindahkan/disalin menggunakan `cp -a` untuk mempertahankan kepemilikan (`www-data:www-data` atau `root:root`) dan permission asli.
   - Verifikasi permission dilakukan dengan `stat` sebelum dan sesudah swap.
3. **Pembaruan Berkas Terpilih (Allowlist Only)**:
   - Hanya berkas dalam kategori aman (dokumentasi, panduan, unit/feature test, scripts) yang diperbarui menggunakan `git checkout -- <explicit list>`.
   - Berkas kode aplikasi inti selain route patch (`app/`, `config/`, `database/`) TIDAK disentuh.
4. **Cadangan Lengkap & Rollback**:
   - Berkas yang disentuh di-backup sebelum eksekusi patch (`/root/backup/live-files-pre-patch/`).
   - Direktori `.git` asli disalin ke `/root/backup/live-git-old` sebelum modifikasi apa pun.
   - Berkas bundle rollback tersedia di `/root/backup/scp-pre-rewrite2.bundle`.

---

## 2. Langkah 0: Aplikasi Patch Terpadu Live (`ALLOW_LIVE_PATCH`)

> [!IMPORTANT]
> **PRASYARAT MUTLAK UNTUK LANGKAH 1:** Langkah ini menerapkan patch terpadu `/root/backup/live-patch-app.patch` (SHA-256: `82536e12ea4e27c7b518972f004057521a2cd0ff542648b01d8de398b38ac8e0`) yang HANYA mencakup berkas aplikasi (tanpa menyentuh atau menghapus berkas dump SQL / kredensial):
> 1. Pembatasan laju (*rate limiting*) `throttle:api-login` pada endpoint legacy `POST /api/login` (tetap aktif), serta `throttle:6,1` pada endpoint reset password & verifikasi email (`routes/api.php`, `routes/auth.php`, `routes/web.php`).
> 2. Perlindungan fallback dekripsi password SMTP (`MailConfigService.php`) jika key rusak.
> 3. Validasi batasan panjang prompt AI chat (`ChatController.php`).
> 4. Perlindungan anti-enumerasi akun dan anonymized audit logging pada forgot-password (`PasswordResetService.php`).
> 5. Bootstrap fallback akun Waka Kurikulum pertama saat `accounts.csv` tidak tersedia (`RealAccountsSeeder.php`).
>
> *(Catatan Keamanan: Patch lawas yang menyertakan penghapusan dump SQL telah diganti namanya menjadi `/root/backup/live-patch-app-WITH-DUMP.patch.SENSITIVE` (mode 600). Berkas ini mengandung data sensitif dari riwayat lama dan DILARANG KERAS disalin ke laptop, arsip zip, git repository, atau kanal chat).*
>
> > **ATURAN MUTLAK:** Jika patch tidak dapat diterapkan secara bersih (baik verifikasi SHA-256 gagal maupun `git apply --check` gagal), **JANGAN JALANKAN LANGKAH 0 ATAU LANGKAH 1**. Hentikan proses dan periksa perbedaan direktori kerja.
>
> **Jika operator melewati Langkah 0, operator WAJIB melewati Langkah 1 juga.** Melewatkan Langkah 0 akan menyebabkan perbedaan pada berkas aplikasi saat `git reset --mixed main` di Langkah 1, sehingga `git status --short` tidak akan bersih dan swap riwayat otomatis dibatalkan.

### Eksekusi Langkah 0

Jalankan perintah berikut di direktori server:

```bash
# 0. Verifikasi Gate File & Pra-Verifikasi Sistem
if [ ! -f /root/backup/ALLOW_LIVE_PATCH ]; then
    echo "ERROR: Gate file /root/backup/ALLOW_LIVE_PATCH tidak ditemukan. Abort."
    exit 1
fi

# 0b. Verifikasi Checksum Patch Terpadu
echo "82536e12ea4e27c7b518972f004057521a2cd0ff542648b01d8de398b38ac8e0  /root/backup/live-patch-app.patch" | sha256sum -c -
if [ $? -ne 0 ]; then
    echo "ERROR: Checksum /root/backup/live-patch-app.patch tidak cocok! Membatalkan eksekusi."
    exit 1
fi

/root/backup/live-verify.sh

cd /var/www/SCP

# 1. Backup berkas aplikasi yang akan dimodifikasi (7 berkas)
mkdir -p /root/backup/live-files-pre-patch
cp -a routes/api.php routes/auth.php routes/web.php \
      app/Http/Controllers/ChatController.php \
      app/Services/MailConfigService.php app/Services/PasswordResetService.php \
      database/seeders/RealAccountsSeeder.php \
      /root/backup/live-files-pre-patch/
echo "Backup berkas aplikasi selesai di /root/backup/live-files-pre-patch/"

# 2. Uji coba patch kering (dry-run)
git apply --check /root/backup/live-patch-app.patch || patch -p1 --dry-run < /root/backup/live-patch-app.patch
if [ $? -ne 0 ]; then
    echo "ERROR: Patch dry-run gagal! Patch tidak berlaku bersih. Jangan jalankan Langkah 0 atau 1."
    exit 1
fi

# 3. Terapkan patch pada working tree live
git apply /root/backup/live-patch-app.patch
echo "Patch terpadu berhasil diterapkan."

# 4. Uji kelayakan konfigurasi, cache rute, dan reload PHP-FPM
HAD_ROUTE_CACHE=0
[ -f bootstrap/cache/routes-v7.php ] && HAD_ROUTE_CACHE=1
php artisan config:clear
php artisan route:clear
[ "$HAD_ROUTE_CACHE" -eq 1 ] && php artisan route:cache
php artisan route:list > /dev/null
systemctl reload php8.3-fpm

# 5. Uji verifikasi penuh pasca-patch (login 5 akun terlindung, token, sesi, DB SHA256)
/root/backup/live-verify.sh
```

### Prosedur Rollback Langkah 0

Jika terjadi kendala setelah patch:

```bash
cd /var/www/SCP
cp -a /root/backup/live-files-pre-patch/* /var/www/SCP/
systemctl reload php8.3-fpm
php artisan config:clear
php artisan route:clear
rm -f /root/backup/ALLOW_LIVE_PATCH
/root/backup/live-verify.sh
echo "Rollback Langkah 0 selesai."
```

# 1. Pulihkan berkas routes asli dari backup
cp -a /root/backup/live-files-pre-patch/* routes/

# 2. Bersihkan cache route dan config
php artisan config:clear
php artisan route:clear

# 3. Verifikasi git status pada routes
git status routes/
```

---

## 3. Langkah 1: Pembersihan & Swap Riwayat Git Live (`ALLOW_LIVE_CLEANUP`)

> [!IMPORTANT]
> Langkah ini mengganti riwayat git `.git` di `/var/www/SCP` dengan riwayat bersih dari `/root/scp-clean`, memindahkan berkas sensitif yang tidak ter-track, dan menyelaraskan working tree melalui mixed reset.

### Eksekusi Langkah 1

Jalankan perintah berikut di direktori server:

```bash
# 0. Verifikasi Gate File
if [ ! -f /root/backup/ALLOW_LIVE_CLEANUP ]; then
    echo "ERROR: Gate file /root/backup/ALLOW_LIVE_CLEANUP tidak ditemukan. Abort."
    exit 1
fi

cd /var/www/SCP
set -euo pipefail

# 1. Pindahkan berkas sensitif, dump SQL, diagram, AI skills, dan skrip scratch ke direktori aman terisolasi
mkdir -p /root/backup/sensitive
chmod 700 /root/backup/sensitive

for f in database/database_dump.sql sqlite_to_mysql.sql .env.testing \
         db_skansaba.sql restore_config.sql SCP_CONTEXT.md SCP_UseCase_Diagram.drawio \
         add_periods.php create_ineffective_days.php dump_sqlite.php generate_models.php \
         patch_conflict.php patch_controllers.php patch_journal.php patch_schedule.php \
         restore_controllers.php restore_models.php rewrite_controllers.php \
         skills-lock.json .agents .claude agent; do
    if [ -e "/var/www/SCP/$f" ]; then
        DEST="/root/backup/sensitive/$(basename "$f")"
        if [ -e "$DEST" ]; then
            echo "ERROR: Berkas tujuan $DEST sudah ada di backup sensitif! Berhenti." >&2
            exit 1
        fi
        mv "/var/www/SCP/$f" "$DEST"
        echo "Pindah berkas sensitif: $f -> $DEST"
    fi
done
chmod 600 /root/backup/sensitive/* 2>/dev/null || true
echo "Berkas sensitif, SQL, AI, dan scratch berhasil diamankan di /root/backup/sensitive/"

# 1b. Pindahkan berkas lawas docs/scripts yang dipindahkan di main ke /root/backup/live-removed-files/ (Preservasi Tanpa Hapus)
mkdir -p /root/backup/live-removed-files
chmod 700 /root/backup/live-removed-files

for f in docs/deploy-runbook.md docs/promote-candidate.md scripts/promote-candidate.sh; do
    if [ -e "/var/www/SCP/$f" ]; then
        DEST="/root/backup/live-removed-files/$f"
        if [ -e "$DEST" ]; then
            echo "ERROR: Berkas tujuan $DEST sudah ada di live-removed-files! Berhenti." >&2
            exit 1
        fi
        mkdir -p "$(dirname "$DEST")"
        mv "/var/www/SCP/$f" "$DEST"
        echo "Pindah berkas repositori dipindahkan: $f -> $DEST"
    fi
done

# 2. Catat stat & ownership .git asli
STAT_BEFORE=$(stat -c '%u:%g %a' /var/www/SCP/.git)
echo "Stat .git asli: $STAT_BEFORE"

# 3. Backup direktori .git live asli
cp -a /var/www/SCP/.git /root/backup/live-git-old
echo "Backup .git live selesai di /root/backup/live-git-old"

# 4. Salin .git bersih dari /root/scp-clean ke lokasi sementara
cp -a /root/scp-clean/.git /var/www/SCP/.git_new

# 5. Samakan kepemilikan .git_new dengan .git asli
chown -R "$(stat -c '%U:%G' /var/www/SCP/.git)" /var/www/SCP/.git_new

# 6. Swap direktori .git (pindahkan .git lama ke /root/backup/live-git-old DI LUAR web tree)
mv /var/www/SCP/.git /root/backup/live-git-old
mv /var/www/SCP/.git_new /var/www/SCP/.git

# 7. Verifikasi stat & ownership setelah swap (harus identik)
STAT_AFTER=$(stat -c '%u:%g %a' /var/www/SCP/.git)
echo "Stat .git baru: $STAT_AFTER"
if [ "$STAT_BEFORE" != "$STAT_AFTER" ]; then
    echo "PERINGATAN: Perbedaan stat terdeteksi! Mengembalikan kepemilikan..."
    chown -R "$(stat -c '%U:%G' /root/backup/live-git-old)" /var/www/SCP/.git
fi

# 8. Set remote URL ke origin resmi
git remote set-url origin git@github.com:Visipir/scp.git || git remote add origin git@github.com:Visipir/scp.git

# 9. Mixed reset ke branch main (working tree TIDAK disentuh/dihapus)
git reset --mixed main

# 10. Checkout allowlist berkas non-aplikasi (computed allowlist)
git checkout -- \
    .gitattributes \
    .gitignore \
    .env.testing.example \
    README.md \
    docs/ \
    phpunit.xml \
    scripts/ \
    tests/

# 11. Periksa status working tree (wajib bersih/kosong tanpa drift)
STATUS=$(git status --short)
if [ -n "$STATUS" ]; then
    echo "ERROR: git status --short tidak kosong! Drift tidak diharapkan:"
    echo "$STATUS"
    echo "Membatalkan dan melakukan rollback ke /root/backup/live-git-old..."
    mv /var/www/SCP/.git /root/backup/live-git-failed
    cp -a /root/backup/live-git-old /var/www/SCP/.git
    git reset --mixed 7ee509f6cb5d6e951966b3f4d6ed443b6aba3435
    exit 1
fi
echo "PASS: git status --short bersih"

# 12. Kadaluwarsa reflog & garbage collection untuk memusnahkan objek lama
git reflog expire --expire=now --all
git gc --prune=now

# 13. Verifikasi Keberhasilan Pasca-Pembersihan
echo "--- Verifikasi Objek Lama ---"
git cat-file -e 433dc1164c62998d4e863b31563b7c9e7ac5e599 2>/dev/null && echo "FAIL: dump blob masih ada" || echo "PASS: dump blob musnah"
git cat-file -e 7ee509f 2>/dev/null && echo "FAIL: commit 7ee509f masih ada" || echo "PASS: commit 7ee509f musnah"

echo "Total commit live: $(git rev-list --all --count)"

# 14. Jalankan prepush-check pada repositori live
bash scripts/ops/prepush-check.sh

# 15. Verifikasi login akun terlindung & hash DB live
echo "Live DB SHA256: $(sha256sum database/database.sqlite)"
```

### Prosedur Rollback Langkah 1

Jika terjadi anomali atau kesalahan pasca-swap riwayat:

```bash
cd /var/www/SCP

# 1. Kembalikan .git asli dari backup /root/backup/live-git-old (di luar web tree)
mv /var/www/SCP/.git /root/backup/live-git-failed
cp -a /root/backup/live-git-old /var/www/SCP/.git

# 2. Reset mixed ke commit live semula
git reset --mixed 7ee509f6cb5d6e951966b3f4d6ed443b6aba3435

# 3. Bersihkan berkas untracked baru yang ada di clean tree tapi tidak di live HEAD
# a. Dry-run pratinjau penghapusan:
git clean -nd -- docs/ scripts/ tests/

# b. Hapus secara presisi HANYA berkas yang ada di clean tree tapi tidak di live HEAD
# (Daftar di bawah dikomputasi dari: git diff --name-only --diff-filter=A 7ee509f clean_tip)
rm -f \
    .gitattributes \
    .env.testing.example \
    docs/checklist-ukk.md \
    docs/ops/deploy-runbook.md \
    docs/ops/live-repo-cleanup.md \
    docs/ops/promote-candidate.md \
    docs/ops/push-after-rewrite.md \
    scripts/ops/postpush-verify.sh \
    scripts/ops/prepush-check.sh \
    scripts/ops/promote-candidate.sh \
    tests/Feature/AuthRateLimitingQ2Test.php \
    tests/Feature/SmtpGuardTest.php \
    tests/Fixtures/synthetic_fet_timetable.csv \
    tests/Fixtures/synthetic_fet_timetable.xml
rm -rf docs/ops scripts/ops
rm -rf tests/Fixtures/ 2>/dev/null || true

# c. Pulihkan seluruh berkas live asli yang terdaftar di git
git checkout -- .

# 4. Kembalikan berkas yang dipindahkan dari live-removed-files
if [ -d /root/backup/live-removed-files ]; then
    for f in docs/deploy-runbook.md docs/promote-candidate.md scripts/promote-candidate.sh; do
        if [ -e "/root/backup/live-removed-files/$f" ]; then
            mkdir -p "/var/www/SCP/$(dirname "$f")"
            cp -a "/root/backup/live-removed-files/$f" "/var/www/SCP/$f"
            echo "Dipulihkan dari live-removed-files: $f"
        fi
    done
fi

# 5. Kembalikan berkas sensitif, dump SQL, AI skills, diagram, dan skrip scratch dari backup jika diperlukan
for f in database_dump.sql sqlite_to_mysql.sql .env.testing \
         db_skansaba.sql restore_config.sql SCP_CONTEXT.md SCP_UseCase_Diagram.drawio \
         add_periods.php create_ineffective_days.php dump_sqlite.php generate_models.php \
         patch_conflict.php patch_controllers.php patch_journal.php patch_schedule.php \
         restore_controllers.php restore_models.php rewrite_controllers.php \
         skills-lock.json .agents .claude agent; do
    [ -e "/root/backup/sensitive/$f" ] && cp -a "/root/backup/sensitive/$f" /var/www/SCP/ 2>/dev/null || true
done
[ -f /var/www/SCP/database_dump.sql ] && mv /var/www/SCP/database_dump.sql /var/www/SCP/database/ 2>/dev/null || true

# 6. Bersihkan direktori failed jika verifikasi rollback selesai
rm -rf /var/www/SCP/.git_failed
php artisan config:clear
php artisan route:clear
echo "Rollback Langkah 1 selesai. Repositori live kembali ke kondisi awal."
```

---

## 4. Pembersihan Arsip Kredensial CSV (`storage/app/private/credentials/`)

Setelah seluruh berkas kredensial sementara (`teacher_credentials_*.csv`, `student_credentials_*.csv`, `bulk_reset_teachers_*.csv`) disalin oleh operator ke mesin lokal via `scp`:
- `bulk_reset_teachers_20261107_145429_6caf.csv` (`9733` bytes, mode `0600`)
- `student_credentials_20261107_025322_s4er.csv` (`128974` bytes, mode `0600`)
- `student_credentials_latest.csv` (`128974` bytes, mode `0600`)
- `teacher_credentials_latest.csv` (`9733` bytes, mode `0600`)

Jalankan perintah penghapusan berikut di server:
```bash
rm -f /var/www/SCP/storage/app/private/credentials/*.csv
```
