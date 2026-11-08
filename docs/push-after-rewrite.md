# Panduan Push Setelah Pembersihan Riwayat Git (History Rewrite)

> [!CAUTION]
> **JANGAN MENJALANKAN `git push` SECARA OTOMATIS.**
> Seluruh perintah pada dokumen ini wajib dijalankan secara manual oleh pemilik repositori setelah menyelesaikan langkah verifikasi prasyarat (rotasi kunci dan repositori privat).

---

## 1. Daftar Periksa Manual Operator (*Manual Execution Checklist*)

Ikuti langkah-langkah berikut secara berurutan:

1. [ ] **Rotasi Google Gemini API Key**:
   - Buat API key baru di Google AI Studio / Google Cloud Console.
   - Buka `/waka/settings` di aplikasi web SCP dan simpan API key baru ke dalam konfigurasi sistem.
   - Hapus / nonaktifkan API key lama di konsol Google Cloud.
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
6. [ ] **Eksekusi Push Repositori iOS dari `/root/scp-ios-clean` (Normal Push)**:
   - Ikuti panduan di [`docs/push-ios.md`](file:///root/scp-ios-clean/docs/push-ios.md):
   ```bash
   cd /root/scp-ios-clean
   git push origin main
   ```
7. [ ] **Kirim Permohonan Pembersihan Cache ke GitHub Support**:
   - Jika salah satu repositori pernah berstatus publik atau memiliki fork/pull request, buka tiket di [GitHub Support](https://support.github.com/contact):
   - Minta GitHub menjalankan `git gc` dan menghapus unreachable commits, PR refs, dan cached commit views.
8. [ ] **Unduh & Hapus Berkas Kredensial CSV**:
   - Pola berkas riil di `/var/www/SCP/storage/app/private/credentials/`: `teacher_credentials_*.csv`, `student_credentials_*.csv`, `bulk_reset_teachers_*.csv`:
     - `bulk_reset_teachers_20261107_145429_6caf.csv` (`9733` bytes, mode `0600`)
     - `student_credentials_20261107_025322_s4er.csv` (`128974` bytes, mode `0600`)
     - `student_credentials_latest.csv` (`128974` bytes, mode `0600`)
     - `teacher_credentials_latest.csv` (`9733` bytes, mode `0600`)
   - Salin berkas ke mesin lokal via `scp`, distribusikan, lalu hapus dari server:
     ```bash
     rm -f /var/www/SCP/storage/app/private/credentials/*.csv
     ```
9. [ ] **Eksekusi Langkah 0 Pembersihan Live (Patch Routes)**:
   - Buat berkas gerbang: `touch /root/backup/ALLOW_LIVE_PATCH`
   - Jalankan Langkah 0 pada [`docs/live-repo-cleanup.md`](file:///root/scp-clean/docs/live-repo-cleanup.md).
10. [ ] **Eksekusi Langkah 1 Pembersihan Live (Swap .git History)**:
    - Buat berkas gerbang: `touch /root/backup/ALLOW_LIVE_CLEANUP`
    - Jalankan Langkah 1 pada [`docs/live-repo-cleanup.md`](file:///root/scp-clean/docs/live-repo-cleanup.md).
11. [ ] **Pembersihan Cadangan Server Pasca-Penilaian**:
    - Setelah penilaian/grading selesai, hapus direktori `/root/backup/` (berkas bundle, scan-terms, live-git-old, dan sensitive).

---

## 2. Tabel Ringkasan Komit, Branch, Tag, dan Berkas Bundle

### A. Repositori Web (`/root/scp-clean`)
- **Total Komit (`git rev-list --all --count`)**: `124`
- **Branch Aktif**:
  - `feat/dark-console-ui`: HEAD (komit dokumentasi dan hardening route/privasi)
  - `main`: `5a73e1644a77b64d9ed4bfc945b84ceb44b63c21` (17 komit)
- **Tag**:
  - `pre-restyle`: `5a73e1644a77b64d9ed4bfc945b84ceb44b63c21`
- **Hasil Pengujian Unit/Fitur**:
  - `331 tests, 330 passed, 1 skipped` (0 failures, 0 errors, 2348 assertions).

### B. Repositori iOS (`/root/scp-ios-clean`)
- **Total Komit (`git rev-list --all --count`)**: `93`
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
| `scp-clean-HEAD.bundle` | ~1.0 MB | *(Dihitung ulang pasca-komit final)* |
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
