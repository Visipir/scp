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
| **Langkah 0** (Patch Routes) | `/root/backup/ALLOW_LIVE_PATCH` | **NOT READY** | Berkas gerbang belum dibuat oleh operator; patch `/root/backup/live-patch-routes.patch` harus ditinjau terlebih dahulu. |
| **Langkah 1** (Swap Riwayat) | `/root/backup/ALLOW_LIVE_CLEANUP` | **NOT READY** | Berkas gerbang belum dibuat oleh operator; mensyaratkan Langkah 0 selesai dan tervalidasi penuh terlebih dahulu. |

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

## 2. Langkah 0: Aplikasi Patch Routes Live (`ALLOW_LIVE_PATCH`)

> [!IMPORTANT]
> Langkah ini menerapkan pembatasan laju (*rate limiting*) `throttle:api-login` pada endpoint legacy `POST /api/login` (tetap aktif), serta `throttle:6,1` pada endpoint reset password & verifikasi email pada instans live tanpa menonaktifkan endpoint apa pun (*throttle-only patch*). Varian 410 disimpan di `/root/backup/live-patch-routes-410.patch` (tidak direkomendasikan).

### Eksekusi Langkah 0

Jalankan perintah berikut di direktori server:

```bash
# 0. Verifikasi Gate File
if [ ! -f /root/backup/ALLOW_LIVE_PATCH ]; then
    echo "ERROR: Gate file /root/backup/ALLOW_LIVE_PATCH tidak ditemukan. Abort."
    exit 1
fi

cd /var/www/SCP

# 1. Backup berkas routes yang akan dimodifikasi
mkdir -p /root/backup/live-files-pre-patch
cp -a routes/api.php routes/auth.php routes/web.php /root/backup/live-files-pre-patch/
echo "Backup routes selesai di /root/backup/live-files-pre-patch/"

# 2. Uji coba patch kering (dry-run)
patch -p1 --dry-run < /root/backup/live-patch-routes.patch || git apply --check /root/backup/live-patch-routes.patch
if [ $? -ne 0 ]; then
    echo "ERROR: Patch dry-run gagal! Membatalkan eksekusi."
    exit 1
fi

# 3. Terapkan patch pada working tree live
git apply /root/backup/live-patch-routes.patch
echo "Patch routes berhasil diterapkan."

# 4. Uji kelayakan konfigurasi & route list (cek apakah route cache routes-v7.php aktif)
HAD_ROUTE_CACHE=0
[ -f bootstrap/cache/routes-v7.php ] && HAD_ROUTE_CACHE=1
php artisan config:clear
php artisan route:clear
[ "$HAD_ROUTE_CACHE" -eq 1 ] && php artisan route:cache
php artisan route:list > /dev/null

# 5. Uji login nyata 5 akun terlindung (read-only, via port live 127.0.0.1:85)
# Membaca password dari storage/app/private/protected-verify.env tanpa mencetaknya
bash -c '
set -e
if [ -f storage/app/private/protected-verify.env ]; then
    set -a
    source storage/app/private/protected-verify.env
    set +a
    declare -A MAP=(
        ["waka_kurikulum"]="$WAKA_PASSWORD"
        ["haris-budiawan"]="$HARIS_PASSWORD"
        ["ryawidyaningtyas"]="$RYAWIDYANINGTYAS_PASSWORD"
        ["tim_kesiswaan"]="$TIM_KESISWAAN_PASSWORD"
        ["17766"]="$SISWA_17766_PASSWORD"
    )
    for u in waka_kurikulum haris-budiawan ryawidyaningtyas tim_kesiswaan 17766; do
        pw="${MAP[$u]}"
        jar=$(mktemp)
        csrf=$(curl -s -c "$jar" http://127.0.0.1:85/login | grep -oP '\''name="_token" value="\K[^"]+'\'' | head -n1)
        web_code=$(curl -s -b "$jar" -o /dev/null -w "%{http_code}" -X POST http://127.0.0.1:85/login \
            --data-urlencode "_token=$csrf" --data-urlencode "username=$u" --data-urlencode "password=$pw")
        rm -f "$jar"
        api_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST http://127.0.0.1:85/api/v1/auth/login \
            -H "Accept: application/json" -H "Content-Type: application/json" \
            -d "{\"username\":\"$u\",\"password\":\"$pw\"}")
        echo "Login $u -> Web: HTTP $web_code (expected 302), API v1: HTTP $api_code (expected 200)"
    done
fi
'

# 6. Konfirmasi integritas live DB
echo "Live DB SHA256: $(sha256sum database/database.sqlite)"
echo "User count: $(sqlite3 database/database.sqlite 'SELECT count(*) FROM users;')"
```

### Prosedur Rollback Langkah 0

Jika terjadi kendala pada routing setelah patch:

```bash
cd /var/www/SCP

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

# 1. Pindahkan 3 berkas sensitif tidak terlacak ke direktori aman terisolasi
mkdir -p /root/backup/sensitive
chmod 700 /root/backup/sensitive

if [ -f /var/www/SCP/database/database_dump.sql ]; then
    mv /var/www/SCP/database/database_dump.sql /root/backup/sensitive/
fi
if [ -f /var/www/SCP/sqlite_to_mysql.sql ]; then
    mv /var/www/SCP/sqlite_to_mysql.sql /root/backup/sensitive/
fi
if [ -f /var/www/SCP/.env.testing ]; then
    mv /var/www/SCP/.env.testing /root/backup/sensitive/
fi
chmod 600 /root/backup/sensitive/* 2>/dev/null || true
echo "Berkas sensitif berhasil diamankan di /root/backup/sensitive/"

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

# 9. Mixed reset ke branch feat/dark-console-ui (working tree TIDAK disentuh/dihapus)
git reset --mixed feat/dark-console-ui

# 10. Checkout HANYA berkas kategori aman (docs, tests, scripts, fixtures)
git checkout -- \
    .gitignore \
    .env.testing.example \
    README.md \
    SCP_CONTEXT.md \
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
bash scripts/prepush-check.sh

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
# (Daftar di bawah dikomputasi dari: git diff --name-only --diff-filter=A live_head clean_tip)
rm -f \
    docs/checklist-ukk.md \
    docs/live-repo-cleanup.md \
    docs/push-after-rewrite.md \
    scripts/postpush-verify.sh \
    scripts/prepush-check.sh \
    tests/Feature/AuthRateLimitingQ2Test.php \
    tests/Fixtures/synthetic_fet_timetable.csv \
    tests/Fixtures/synthetic_fet_timetable.xml \
    .env.testing.example
rm -rf tests/Fixtures/ 2>/dev/null || true

# c. Pulihkan seluruh berkas live asli yang terdaftar di git
git checkout -- .

# 4. Kembalikan berkas sensitif dari backup jika diperlukan
if [ -f /root/backup/sensitive/database_dump.sql ]; then
    cp -a /root/backup/sensitive/database_dump.sql /var/www/SCP/database/
fi
if [ -f /root/backup/sensitive/sqlite_to_mysql.sql ]; then
    cp -a /root/backup/sensitive/sqlite_to_mysql.sql /var/www/SCP/
fi
if [ -f /root/backup/sensitive/.env.testing ]; then
    cp -a /root/backup/sensitive/.env.testing /var/www/SCP/
fi

# 5. Bersihkan direktori failed jika verifikasi rollback selesai
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
