# Panduan Push Setelah Pembersihan Riwayat Git (History Rewrite)

> [!CAUTION]
> **JANGAN MENJALANKAN `git push` SECARA OTOMATIS.**
> Seluruh perintah pada dokumen ini wajib dijalankan secara manual oleh pemilik repositori setelah menyelesaikan langkah verifikasi prasyarat (rotasi kunci dan repositori privat).

---

## 1. Daftar Periksa Manual Operator (*Manual Execution Checklist*)

Ikuti langkah-langkah berikut secara berurutan:

1. [ ] **Rotasi Google Gemini API Key & Verifikasi SSH**:
   - Buat API key baru di Google AI Studio / Google Cloud Console.
   - Buka `/waka/settings` di aplikasi web SCP dan simpan API key baru ke dalam konfigurasi sistem.
   - Hapus / nonaktifkan API key lama di konsol Google Cloud.
   - **Operator Pre-Check Kunci SSH GitHub**:
     ```bash
     ssh -T git@github.com
     ```
     Greeting wajib menampilkan: `Hi Visipir! You've successfully authenticated...`. Jika menyapa akun selain `Visipir` atau ditolak, STOP segera.
2. [ ] **Setel Repositori GitHub ke Status Privat & Default Branch**:
   - Buka pengaturan repositori web `git@github.com:Visipir/scp.git` -> Ubah visibilitas ke **Private**.
   - Buka pengaturan repositori iOS `git@github.com:Visipir/scp-ios.git` -> Ubah visibilitas ke **Private**.
   - Pastikan default branch di GitHub adalah **`main`** (Settings -> Branches). Branch `main` adalah branch utama untuk review dan deployment.
3. [ ] **Jalankan Pemeriksaan Remote & Pra-Dorongan (*Pre-Push Check*)**:
   - Periksa status remote origin:
     ```bash
     cd /root/scp-clean && git ls-remote origin
     cd /root/scp-ios-clean && git ls-remote origin
     ```
     *Catatan Ekspektasi:* Jika repositori GitHub baru dibuat / kosong, output kosong (*nothing returned*). Jika repositori memuat riwayat lama, daftar refs akan muncul dan First Push akan menimpa riwayat lama tersebut.
   - Di direktori web: `cd /root/scp-clean && ./scripts/ops/prepush-check.sh`
   - Di direktori iOS: `cd /root/scp-ios-clean && ./scripts/ops/prepush-check.sh`
   - Keduanya wajib menghasilkan status `=== OK: Pre-push checks PASSED. ===`.
4. [ ] **Eksekusi FIRST PUSH (Inisialisasi Repositori Bersih)**:
   - **Repositori Web (`/root/scp-clean`)**: Force push dilakukan HANYA pada first push untuk mengunggah riwayat bersih dan menggantikan riwayat lama di GitHub:
     ```bash
     cd /root/scp-clean
     git push --force origin main feat/dark-console-ui
     git push --force origin pre-restyle
     ```
   - **Repositori iOS (`/root/scp-ios-clean`)**: Plain push normal tanpa force:
     ```bash
     cd /root/scp-ios-clean
     git push origin main
     ```
5. [ ] **Jalankan Verifikasi Pasca-Dorongan (*Post-Push Verification*)**:
   ```bash
   cd /root/scp-clean
   ./scripts/ops/postpush-verify.sh git@github.com:Visipir/scp.git $(git rev-parse HEAD) $(git rev-list --all --count)
   ```
6. [ ] **Aturan LATER PUSHES (Dorongan Rutin Berikutnya)**:
   - Untuk semua komit fitur atau perbaikan di masa mendatang, gunakan dorongan normal tanpa force:
     ```bash
     # Web
     cd /root/scp-clean
     git push origin main

     # iOS
     cd /root/scp-ios-clean
     git push origin main
     ```
   - **PENTING:** Jika dorongan ditolak (non-fast-forward / rejected), **STOP SEGERA dan konsultasikan dengan operator**. Jangan pernah gunakan `git push --force` lagi setelah First Push selesai.
7. [ ] **Verifikasi Remote Tags dan Branches Pasca-Push**:
   - Periksa remote ref di kedua repositori:
   ```bash
   git -C /root/scp-clean ls-remote --heads --tags origin
   git -C /root/scp-ios-clean ls-remote --heads --tags origin
   ```
   - Target ref web yang sah: `refs/heads/main`, `refs/heads/feat/dark-console-ui`, `refs/tags/pre-restyle`.
   - Target ref iOS yang sah: `refs/heads/main`.
   - Jika ditemukan branch/tag asing di GitHub, hapus ref tersebut (`git push origin --delete <stale-ref>`).
8. [ ] **Unduh & Hapus Berkas Kredensial CSV (Perbandingan SHA-256 Wajib Sebelum Hapus)**:
   - Pola berkas riil di `/var/www/SCP/storage/app/private/credentials/`:
     - `bulk_reset_teachers_20261107_145429_6caf.csv` (`9733` bytes, mode `0600`)
     - `student_credentials_20261107_025322_s4er.csv` (`128974` bytes, mode `0600`)
     - `student_credentials_latest.csv` (`128974` bytes, mode `0600`)
     - `teacher_credentials_latest.csv` (`9733` bytes, mode `0600`)
   - **Langkah 1 (Server): Hitung SHA-256 berkas sebelum disalin**:
     ```bash
     sha256sum /var/www/SCP/storage/app/private/credentials/*.csv > /tmp/credentials-server.sha256
     cat /tmp/credentials-server.sha256
     ```
   - **Langkah 2 (Laptop): Salin 4 berkas ke laptop via `scp` & hitung SHA-256 lokal**:
     ```bash
     scp "root@<SERVER_IP>:/var/www/SCP/storage/app/private/credentials/*.csv" ./
     sha256sum *.csv
     ```
   - **Langkah 3 (Verifikasi Kesetaraan Wajib)**:
     - Bandingkan setiap baris hash SHA-256 lokal di laptop dengan hash server. Seluruh hash wajib cocok persis (*byte-identical*).
   - **Langkah 4 (Server): Hapus berkas hanya setelah hash terbukti identik**:
     ```bash
     rm -f \
       /var/www/SCP/storage/app/private/credentials/bulk_reset_teachers_20261107_145429_6caf.csv \
       /var/www/SCP/storage/app/private/credentials/student_credentials_20261107_025322_s4er.csv \
       /var/www/SCP/storage/app/private/credentials/student_credentials_latest.csv \
       /var/www/SCP/storage/app/private/credentials/teacher_credentials_latest.csv \
       /tmp/credentials-server.sha256
     ```
9. [ ] **Pemeriksaan Pra-Patch Dekripsi SMTP & Eksekusi Patch Terpadu Live**:
   - Jalankan `/root/backup/live-verify.sh`
   - Jalankan `/root/backup/check-smtp-decrypt.sh`. Jika FAIL, operator login ke `/settings/email`, simpan ulang password SMTP, dan uji coba sampai PASS.
   - **PRASYARAT MUTLAK UNTUK LANGKAH 10:** Jika Langkah 9 dilewati, Langkah 10 WAJIB dilewati juga. Melewatkan Langkah 9 akan menyisakan berkas aplikasi kotor saat reset riwayat di Langkah 10 sehingga swap otomatis gagal.
   - Buat berkas gerbang: `touch /root/backup/ALLOW_LIVE_PATCH && chmod 600 /root/backup/ALLOW_LIVE_PATCH`
   - Terapkan patch terpadu `live-patch-app.patch` (`git apply --check /root/backup/live-patch-app.patch && git apply /root/backup/live-patch-app.patch`).
   - Jalankan pembersihan/pembaruan cache dan reload PHP-FPM: `php artisan config:clear && php artisan route:clear && systemctl reload php8.3-fpm`.
   - Jalankan kembali `/root/backup/live-verify.sh`.
10. [ ] **Eksekusi Swap .git History Live (Pindahkan ke `/root/backup/live-git-old`)**:
    - Jalankan `/root/backup/live-verify.sh`
    - Buat berkas gerbang: `touch /root/backup/ALLOW_LIVE_CLEANUP && chmod 600 /root/backup/ALLOW_LIVE_CLEANUP`
    - Pindahkan `.git` lama langsung ke `/root/backup/live-git-old` (di luar web tree, JANGAN buat `.git_bak`).
    - Jalankan Langkah 1 pada [`docs/ops/live-repo-cleanup.md`](file:///root/scp-clean/docs/ops/live-repo-cleanup.md).
    - Pastikan `git status --short` benar-benar kosong; jika ada file tak terduga, batalkan dan kembalikan `.git` dari `/root/backup/live-git-old`.
    - Jalankan kembali `/root/backup/live-verify.sh`.
11. [ ] **Pembersihan Token Uji Coba (Ketat & Terarah)**:
    - Jalankan `/root/backup/live-verify.sh`
    - Buat berkas gerbang: `touch /root/backup/ALLOW_TOKEN_CLEANUP && chmod 600 /root/backup/ALLOW_TOKEN_CLEANUP`
    - Jalankan skrip audit token pada Step 11 RUNSHEET.md (verifikasi tepat 14 token uji coba ID 98–111, nama "ios", unconsumed, hanya 5 akun terlindung).
    - Jalankan kembali `/root/backup/live-verify.sh`.
12. [ ] **Pembersihan Cadangan Server Pasca-Penilaian**:
    - Setelah penilaian/grading selesai, hapus direktori `/root/backup/` (`rm -rf /root/backup`). Tidak ada berkas `.git_bak` atau repositori cadangan di dalam `/var/www/SCP/`.

---

## 2. Tabel Ringkasan Komit, Branch, Tag, dan Berkas Bundle

### A. Repositori Web (`/root/scp-clean`)
- **Total Komit**: Dihitung dinamis via `$(git rev-list --all --count)`
- **Branch Aktif**:
  - `main`: HEAD (`$(git rev-parse HEAD)`) — default branch untuk review & deployment
  - `feat/dark-console-ui`: HEAD (komit identik dengan `main`; penghapusan di GitHub opsional)
- **Tag**:
  - `pre-restyle`: `00d1c0ca1ac16ea8f74270a1db6048fd9a1bf448` (lihat bagian History Facts di bawah)
- **Hasil Pengujian Unit/Fitur**:
  - `338 tests, 337 passed, 1 skipped` (0 failures, 0 errors, 2378 assertions).

### B. Repositori iOS (`/root/scp-ios-clean`)
- **Total Komit**: Dihitung dinamis via `$(git rev-list --all --count)`
- **Branch Aktif**:
  - `main`: HEAD (`$(git rev-parse HEAD)`)
- **Status Audit Keamanan**:
  - 0 pelanggaran NIS/NIP/prefix/secret pada seluruh riwayat komit.
  - **Keputusan**: Normal push tanpa rewriting history.

### C. Checksum Berkas Cadangan Bundle (`/root/backup/`)

| Berkas Bundle | Ukuran | SHA-256 Checksum |
|---|---|---|
| `final-web.bundle` / `scp-clean-HEAD.bundle` | Dihitung dinamis saat rebuild | Tercatat di `/root/backup/RUNSHEET.md` via `sha256sum /root/backup/final-web.bundle` |
| `final-ios.bundle` | 518,484 B | `d32cd0874558c9beac5f0bd9f34c513a50c905e5ebf3afa40b9460c131ce8e75` |
| `scp-pre-rewrite2.bundle` | 1.0 MB | `ed2beb3ce7a0cb84883cdd0b0c7ac97c2de4dd4d6a063d27897a4b8c204f25e2` |
| `scp-pre-rewrite.bundle` | 1.2 MB | `11d5489892d08d9fb959e4d6f5813d8a2fbaf2da5aebfe0568ce6977e6d88eb4` |
| `scp-pre-restyle-tag.bundle` | 486 KB | `66c588115e3fc0ca436478a3a209646736703034ddc0557a73f0bf57755f1520` |
| `scp-ios-pre-rewrite2.bundle` | 515 KB | `d15405eddb51a863db4e81ef940eef42520bf89ab526d9bf047adc5b444c4784` |
| `scp-ios-pre-rewrite.bundle` | 516 KB | `8c766cde3ef8ad51365a7c3d7821261a9bfa173f1d94147de8b02aa46cb0dc23` |

### D. Fakta Riwayat Komit & Transisi Status (History Facts)
- **Tag `pre-restyle`**:
  - Menandai kondisi basis kode sebelum restilisasi antarmuka dark console UI.
  - Pada riwayat bersih saat ini (`/root/scp-clean`), komit tag `pre-restyle` adalah `00d1c0ca1ac16ea8f74270a1db6048fd9a1bf448`. Perbedaan hash dari hash historis `5a73e1644a77b64d9ed4bfc945b84ceb44b63c21` disebabkan oleh pembersihan file sensitif/scratch dan pengabaian komit kosong saat proses filter-repo. Pohon direktori kerja (`tree`) terverifikasi semantik identik.
  - Pada cadangan bundle historis (`/root/backup/scp-pre-restyle-tag.bundle`), ref `pre-restyle` tercatat di `5a73e1644a77b64d9ed4bfc945b84ceb44b63c21`.
- **Branch `feat/dark-console-ui` dan `main`**:
  - Branch `main` dimajukan secara fast-forward (`git branch -f main feat/dark-console-ui`) sehingga keduanya berada pada komit tip yang sama (`$(git rev-parse HEAD)`).
- **Pengurangan 19 Berkas**:
  - 19 berkas internal tooling, scratch script, dump SQL, diagram, dan konteks AI (`.agents`, `.claude`, `agent`, `skills-lock.json`, `SCP_CONTEXT.md`, `SCP_UseCase_Diagram.drawio`, `db_skansaba.sql`, `restore_config.sql`, dan 11 file PHP scratch) telah sepenuhnya dihapus dari seluruh commit tree riwayat git. Tidak ada satupun blob maupun commit message yang mereferensikan berkas tersebut.

---

## 3. Peringatan Privasi Alamat Email Komit

> [!WARNING]
> Audit terhadap riwayat komit (`git log --all --format='%an <%ae>'`) mendeteksi alamat email pribadi:
> - `Visipir <fi***@gmail.com>` (tercatat pada komit web dan iOS)
> - `Visipir <vi***@users.noreply.github.com>` (tercatat pada komit iOS)
>
> Jika repositori di-push ke GitHub dan disetel ke Public, alamat email `fi***@gmail.com` akan terlihat secara publik. Oleh karena itu, **pastikan kedua repositori berstatus PRIVATE**.

---

## 4. Eksekusi Dorongan (*Push Execution*)

### A. Repositori Web (`/root/scp-clean`)
Pastikan remote origin benar dan cek kondisi remote:
```bash
cd /root/scp-clean
git remote -v
# origin git@github.com:Visipir/scp.git
```

#### 1. FIRST PUSH (Inisialisasi Riwayat Bersih)
Jalankan blok skrip berikut yang membaca kondisi remote terlebih dahulu:
```bash
cd /root/scp-clean

# 1. Periksa status remote origin terlebih dahulu:
REMOTE_STATE=$(git ls-remote origin)

if [ -z "$REMOTE_STATE" ]; then
    echo "Remote origin kosong (repositori baru). Menjalankan dorongan standar awal..."
    git push origin main feat/dark-console-ui
    git push origin refs/tags/pre-restyle
else
    echo "PERINGATAN: Remote origin sudah memiliki refs:"
    echo "$REMOTE_STATE"
    echo "STOP: Teliti daftar refs di atas. Perintah berikutnya akan menggantikan riwayat remote lama dengan riwayat bersih:"
    # Operator memutuskan dan mengonfirmasi secara sadar:
    git push --force origin main feat/dark-console-ui
    git push --force origin refs/tags/pre-restyle
fi

# 2. Verifikasi pasca-dorongan (dihitung secara runtime tanpa hash hardcoded):
TIP=$(git rev-parse HEAD)
COUNT=$(git rev-list --all --count)
bash scripts/ops/postpush-verify.sh git@github.com:Visipir/scp.git "$TIP" "$COUNT"
```

#### 2. LATER PUSHES (Pengembangan Rutin Selanjutnya)
Setelah First Push berhasil, **SELURUH dorongan berikutnya adalah dorongan biasa (*plain push*)**. Riwayat git **DILARANG KERAS** ditulis ulang lagi (*no filter-repo, no force push*):
```bash
cd /root/scp-clean
git push origin main
```
*Jika ditolak (non-fast-forward / rejected), **STOP SEGERA** dan periksa penyebabnya. JANGAN gunakan `--force`.*

---

### B. Repositori iOS (`/root/scp-ios-clean`)
Pastikan remote origin benar:
```bash
cd /root/scp-ios-clean
git remote -v
# origin git@github.com:Visipir/scp-ios.git
```

#### 1. FIRST PUSH (Inisialisasi Riwayat iOS)
Jalankan blok skrip berikut yang membaca kondisi remote terlebih dahulu:
```bash
cd /root/scp-ios-clean

# 1. Periksa remote state
REMOTE_STATE=$(git ls-remote origin)

if [ -z "$REMOTE_STATE" ]; then
    echo "Remote origin iOS kosong. Menjalankan dorongan awal..."
    git push origin main
else
    echo "PERINGATAN: Remote origin iOS sudah memiliki refs:"
    echo "$REMOTE_STATE"
    echo "STOP: Teliti daftar refs di atas sebelum melanjutkan."
    git push origin main
fi

# 2. Verifikasi pasca-dorongan iOS (dihitung secara runtime tanpa hash hardcoded):
TIP=$(git rev-parse HEAD)
COUNT=$(git rev-list --all --count)
bash scripts/ops/postpush-verify.sh git@github.com:Visipir/scp-ios.git "$TIP" "$COUNT"
```

#### 2. LATER PUSHES (Pengembangan Rutin iOS)
Seluruh dorongan berikutnya adalah dorongan biasa (*plain push*):
```bash
cd /root/scp-ios-clean
git push origin main
```
*Jika ditolak, STOP SEGERA. Jangan menulis ulang riwayat lagi.*

---

### C. Jalur Alternatif: Push dari Mesin Lokal (Laptop) via Bundle
Jika koneksi SSH keluar dari server tidak digunakan, salin berkas bundle ke mesin lokal Anda:
```bash
# Web - First Push
git clone final-web.bundle scp && cd scp
git remote set-url origin git@github.com:Visipir/scp.git

REMOTE_STATE=$(git ls-remote origin)
if [ -z "$REMOTE_STATE" ]; then
    git push origin refs/heads/main:refs/heads/main refs/heads/feat/dark-console-ui:refs/heads/feat/dark-console-ui
    git push origin refs/tags/pre-restyle:refs/tags/pre-restyle
else
    echo "STOP: Teliti remote refs sebelum force push:" && echo "$REMOTE_STATE"
    git push --force origin refs/heads/main:refs/heads/main refs/heads/feat/dark-console-ui:refs/heads/feat/dark-console-ui
    git push --force origin refs/tags/pre-restyle:refs/tags/pre-restyle
fi

TIP=$(git rev-parse HEAD)
COUNT=$(git rev-list --all --count)
bash scripts/ops/postpush-verify.sh git@github.com:Visipir/scp.git "$TIP" "$COUNT"

# Web - Later Pushes
git push origin main

# iOS - First Push & Later Pushes
git clone final-ios.bundle scp-ios && cd scp-ios
git remote set-url origin git@github.com:Visipir/scp-ios.git
git push origin main

TIP=$(git rev-parse HEAD)
COUNT=$(git rev-list --all --count)
bash scripts/ops/postpush-verify.sh git@github.com:Visipir/scp-ios.git "$TIP" "$COUNT"
```

---

## 5. Akses Reviewer, Visibilitas Repositori, dan Manajemen SSH Key

### A. Akses Reviewer pada Repositori Privat
Ketika repositori GitHub disetel menjadi **Private**, seluruh tautan publik sebelumnya akan rusak (*broken link* / HTTP 404). Jika reviewer atau penilai membutuhkan akses ke kode sumber, terdapat dua mekanisme:

1. **Undang Reviewer sebagai Kolaborator GitHub (Settings → Collaborators)**:
   - *Apa yang terekspos:* Seluruh riwayat komit (*git history*), nama komitter, dan alamat email maintainer (`fi***@gmail.com`).
   - *Kelebihan:* Reviewer dapat melihat commit log, diff, file tree, dan branch secara interaktif di antarmuka GitHub.
2. **Bagikan Arsip Snapshot Kode via `git archive` (Tanpa Riwayat Git)**:
   - Buat arsip zip bersih langsung dari commit tip `main`:
     ```bash
     cd /root/scp-clean
     git archive --format=zip --output=/root/backup/scp-main-snapshot.zip main
     ```
   - *Apa yang terekspos:* Hanya *snapshot* berkas kode sumber saat ini. Riwayat komit lampau, commit metadata, dan alamat email maintainer **TIDAK terekspos sama sekali** (tidak memuat direktori `.git`).
   - *Rekomendasi:* Sangat disarankan jika privasi alamat email maintainer ingin dijaga 100% dari pihak luar.

### B. Keputusan Pemilik Terkait SSH Key (`id_ed25519`) Pasca-Push
Kunci SSH privat server (`/root/.ssh/id_ed25519`) digunakan untuk mengautentikasi operasi push ke GitHub akun `Visipir`. Setelah seluruh proses push selesai:
- **Keputusan Pemilik:** Pemilik repositori wajib memutuskan apakah kunci SSH ini akan:
  1. **Dihapus dari GitHub (Direkomendasikan)**: Buka GitHub Settings -> SSH and GPG keys -> Hapus kunci server, ATAU
  2. **Dibatasi aksesnya (*Deploy Key with write access*)**: Jadikan kunci tersebut hanya terikat pada repositori tertentu alih-alih seluruh akun GitHub.
- *Catatan Kepatuhan:* Agent tidak menyentuh, mengubah, maupun menghapus berkas kunci `/root/.ssh/id_ed25519` di server.
