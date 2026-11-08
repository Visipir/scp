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
2. [ ] **Setel Repositori GitHub ke Status Privat**:
   - Buka pengaturan repositori web `git@github.com:Visipir/scp.git` -> Ubah visibilitas ke **Private**.
   - Buka pengaturan repositori iOS `git@github.com:Visipir/scp-ios.git` -> Ubah visibilitas ke **Private**.
3. [ ] **Jalankan Skrip Pemeriksaan Pra-Dorongan (*Pre-push Safety Check*)**:
   - Di direktori web: `cd /root/scp-clean && ./scripts/prepush-check.sh`
   - Di direktori iOS: `cd /root/scp-ios-clean && ./scripts/prepush-check.sh`
   - Keduanya wajib menghasilkan status `=== OK: Pre-push checks PASSED. ===`.
4. [ ] **Eksekusi Force Push Repositori Web dari `/root/scp-clean`**:
   ```bash
   cd /root/scp-clean
   git push --force origin --all
   git push --force origin --tags
   ```
5. [ ] **Jalankan Verifikasi Pasca-Dorongan (*Post-Push Verification*)**:
   ```bash
   cd /root/scp-clean
   ./scripts/postpush-verify.sh git@github.com:Visipir/scp.git $(git rev-list --all --count)
   ```
6. [ ] **Eksekusi Push Repositori iOS dari `/root/scp-ios-clean` (Normal Push, Tanpa Force)**:
   - Push ke remote iOS dilakukan secara normal tanpa `--force` (`git push origin main`).
   - *Alasan:* Repositori iOS **TIDAK PERNAH di-rewrite** — seluruh 93 komit dalam riwayatnya telah dipindai dan menghasilkan 0 temuan. HEAD secara bersih berada 10 komit mendahului origin/main (`9be56e9..7f4ad93`). Push bersifat fast-forward murni. Jika git menolak dengan status non-fast-forward, git menolak secara otomatis untuk mencegah penimpaan perubahan tak terduga, bukan alasan untuk force-push.
   ```bash
   cd /root/scp-ios-clean
   git push origin main
   ```

   > [!IMPORTANT]
   > **Aturan Pasca-Push (Jangan Pernah Rewrite Riwayat Lagi):** Setelah kedua repositori didorong ke GitHub, riwayat komit tidak boleh di-rewrite lagi. Seluruh komit baru di masa mendatang harus berupa komit normal (`git commit`) dan dorongan normal (`git push origin <branch>`). Jangan pernah menjalankan `git-filter-repo`, rebase interaktif, atau push paksa (`--force`) setelah proses ini selesai.
7. [ ] **Verifikasi Remote Tags dan Branches Pasca-Push**:
   - Periksa remote ref di kedua repositori:
   ```bash
   git -C /root/scp-clean ls-remote --heads --tags origin
   git -C /root/scp-ios-clean ls-remote --heads --tags origin
   ```
   - Target ref web yang sah: `refs/heads/main`, `refs/heads/feat/dark-console-ui`, `refs/tags/pre-restyle`.
   - Target ref iOS yang sah: `refs/heads/main`.
   - Jika ditemukan branch/tag asing di GitHub, hapus ref tersebut (`git push origin --delete <stale-ref>`).
8. [ ] **Unduh & Hapus Berkas Kredensial CSV (Tanpa Menyisakan Arsip di Server)**:
   - Pola berkas riil di `/var/www/SCP/storage/app/private/credentials/`:
     - `bulk_reset_teachers_20261107_145429_6caf.csv` (`9733` bytes, mode `0600`)
     - `student_credentials_20261107_025322_s4er.csv` (`128974` bytes, mode `0600`)
     - `student_credentials_latest.csv` (`128974` bytes, mode `0600`)
     - `teacher_credentials_latest.csv` (`9733` bytes, mode `0600`)
   - Salin 4 berkas ke laptop via `scp`:
     ```bash
     scp "root@<SERVER_IP>:/var/www/SCP/storage/app/private/credentials/*.csv" ./
     ```
   - Bandingkan checksum `sha256sum` lokal di laptop dengan `sha256sum` di server.
   - Hapus berkas persis di server setelah checksum identik; **jangan tinggalkan berkas arsip zip/tar apa pun di server**:
     ```bash
     rm -f \
       /var/www/SCP/storage/app/private/credentials/bulk_reset_teachers_20261107_145429_6caf.csv \
       /var/www/SCP/storage/app/private/credentials/student_credentials_20261107_025322_s4er.csv \
       /var/www/SCP/storage/app/private/credentials/student_credentials_latest.csv \
       /var/www/SCP/storage/app/private/credentials/teacher_credentials_latest.csv
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
    - Jalankan Langkah 1 pada [`docs/live-repo-cleanup.md`](file:///root/scp-clean/docs/live-repo-cleanup.md).
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
- **Total Komit**: `$(git rev-list --all --count)` (dihitung dinamis saat eksekusi)
- **Branch Aktif**:
  - `feat/dark-console-ui`: HEAD (komit dokumentasi dan hardening route/privasi)
  - `main`: `5a73e1644a77b64d9ed4bfc945b84ceb44b63c21` (17 komit)
- **Tag**:
  - `pre-restyle`: `5a73e1644a77b64d9ed4bfc945b84ceb44b63c21`
- **Hasil Pengujian Unit/Fitur**:
  - `337 tests, 336 passed, 1 skipped` (0 failures, 0 errors, 2371 assertions).

### B. Repositori iOS (`/root/scp-ios-clean`)
- **Total Komit**: `$(git rev-list --all --count)` (dihitung dinamis saat eksekusi: 93)
- **Branch Aktif**:
  - `main`: `7f4ad93b28ca8b1874ed7345a43dc4909de8af18`
- **Komit di `origin/main` lama**:
  - `9be56e9d59ef7210456c45755740a9384eeaf352` (repositori iOS 10 komit mendahului origin)
- **Status Audit Keamanan**:
  - 0 pelanggaran NIS/NIP/prefix/secret pada seluruh riwayat komit.
  - **Keputusan**: Normal push tanpa rewriting history.

### C. Checksum Berkas Cadangan Bundle (`/root/backup/`)

| Berkas Bundle | Ukuran | SHA-256 Checksum |
|---|---|---|
| `final-web.bundle` / `scp-clean-HEAD.bundle` | 1,069,704 B | `cd34a3f0521ab7a6af8b3c2e9f1c259d7da353026ebba0542cc5e9946d2a92e0` |
| `final-ios.bundle` | 518,484 B | `d32cd0874558c9beac5f0bd9f34c513a50c905e5ebf3afa40b9460c131ce8e75` |
| `scp-pre-rewrite2.bundle` | 1.0 MB | `ed2beb3ce7a0cb84883cdd0b0c7ac97c2de4dd4d6a063d27897a4b8c204f25e2` |
| `scp-pre-rewrite.bundle` | 1.2 MB | `11d5489892d08d9fb959e4d6f5813d8a2fbaf2da5aebfe0568ce6977e6d88eb4` |
| `scp-pre-restyle-tag.bundle` | 486 KB | `66c588115e3fc0ca436478a3a209646736703034ddc0557a73f0bf57755f1520` |
| `scp-ios-pre-rewrite2.bundle` | 515 KB | `d15405eddb51a863db4e81ef940eef42520bf89ab526d9bf047adc5b444c4784` |
| `scp-ios-pre-rewrite.bundle` | 516 KB | `8c766cde3ef8ad51365a7c3d7821261a9bfa173f1d94147de8b02aa46cb0dc23` |

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
Pastikan remote origin benar:
```bash
cd /root/scp-clean
git remote -v
# origin git@github.com:Visipir/scp.git
```
Jalankan dorongan paksa:
```bash
git push --force origin --all
git push --force origin --tags
```

Lakukan verifikasi pasca-dorongan:
```bash
./scripts/postpush-verify.sh git@github.com:Visipir/scp.git $(git rev-list --all --count)
```

### B. Repositori iOS (`/root/scp-ios-clean`)
Pastikan remote origin benar:
```bash
cd /root/scp-ios-clean
git remote -v
# origin git@github.com:Visipir/scp-ios.git
```
Jalankan dorongan normal (tanpa `--force`):
```bash
git push origin main
```

### C. Jalur Alternatif: Push dari Mesin Lokal (Laptop) via Bundle
Jika koneksi SSH keluar dari server tidak digunakan, salin berkas `/root/backup/final-web.bundle` dan `/root/backup/final-ios.bundle` ke mesin lokal Anda:
```bash
# Web
git clone final-web.bundle scp && cd scp
git remote set-url origin git@github.com:Visipir/scp.git
./scripts/prepush-check.sh
git push --force origin --all && git push --force origin --tags

# iOS
git clone final-ios.bundle scp-ios && cd scp-ios
git remote set-url origin git@github.com:Visipir/scp-ios.git
./scripts/prepush-check.sh
git push origin main
```
