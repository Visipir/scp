# Panduan Push Setelah Pembersihan Riwayat Git (History Rewrite)

> [!CAUTION]
> **JANGAN MENJALANKAN `git push` SECARA OTOMATIS.**
> Seluruh perintah pada dokumen ini wajib dijalankan secara manual oleh pemilik repositori setelah menyelesaikan langkah verifikasi prasyarat (rotasi kunci dan repositori privat).

---

## Langkah 1: Prasyarat Keamanan (Rotasi Kunci & Repositori Privat)

Sebelum melakukan dorongan (*push*) ke remote GitHub:
1. **Rotasi Google Gemini API Key**: Pastikan API key yang pernah tercantum dalam commit awal telah di-revoke dan diganti dengan API key baru via Google AI Studio / Google Cloud Console.
2. **Status Repositori Privat**: Pastikan visibilitas repositori GitHub `git@github.com:Visipir/scp.git` berada dalam status **Private**.

---

## Langkah 2: Konfirmasi Remote Origin & Struktur Branch

> [!CAUTION]
> **EKSEKUSI PUSH HANYA DARI `/root/scp-clean`**
> PUSH WAJIB DIJALANKAN DARI DIREKTORI `/root/scp-clean`. JANGAN PERNAH MENJALANKAN PUSH DARI `/var/www/SCP` KARENA DIREKTORI `.git` PADA LIVE MASIH MENYIMPAN RIWAYAT LAMA SEBELUM LANGKAH N6 DILAKSANAKAN.

Pastikan Anda berada di direktori clone bersih `/root/scp-clean`:
```bash
cd /root/scp-clean
git remote -v
```
Output yang diharapkan:
```text
origin  git@github.com:Visipir/scp.git (fetch)
origin  git@github.com:Visipir/scp.git (push)
```

Pastikan hanya dua branch utama dan satu tag yang eksis:
- Branch: `main` dan `feat/dark-console-ui`
- Tag: `pre-restyle`

Tidak ada branch atau tag lain yang menyimpan riwayat lama (*unrewritten history*).

Jalankan skrip pemeriksaan pra-dorongan (*pre-push safety check*):
```bash
./scripts/prepush-check.sh
```
Perintah ini wajib menghasilkan `=== OK: Pre-push checks PASSED. Repository is safe for push. ===` sebelum melanjutkan.

---

## Langkah 3: Eksekusi Force Push ke Remote Origin

> [!IMPORTANT]
> **Mengapa Menggunakan `--force` Bukan `--force-with-lease`?**
> Perkakas `git-filter-repo` melepaskan dan menghapus referensi *remote-tracking* (`refs/remotes/origin/*`). Karena clone lokal yang telah di-rewrite tidak lagi memiliki referensi pelacak remote lama untuk dibandingkan (*no remote-tracking comparison baseline*), opsi `--force-with-lease` akan ditolak (*rejected*) atau memberikan ilusi proteksi yang keliru (*false safety*). Oleh karena itu, gunakan opsi `--force` secara eksplisit pada branch dan tag.

Jalankan perintah berikut:
```bash
# 1. Force push seluruh branch yang telah dibersihkan
git push --force origin --all

# 2. Force push seluruh tag yang telah dibersihkan
git push --force origin --tags
```

---

## Langkah 4: Pembersihan Server GitHub (Support Ticket)

Jika repositori pernah berstatus **Public** atau memiliki **Forks / Pull Requests**:
GitHub tetap menyimpan commit lama yang tidak terhubung (*unreachable commits*) dalam cache internal dan tampilan commit PR. Hubungi GitHub Support untuk membersihkannya secara permanen:
1. Kunjungi [GitHub Support Portal](https://support.github.com/contact).
2. Topik: **Git and GitHub / Removing sensitive data**.
3. Lampirkan permohonan pembersihan:
   > *"Please run git gc and purge orphaned unreachable commits, pull request refs, and cached commit views on repository Visipir/scp following a sensitive data removal via git-filter-repo."*

---

## Langkah 5: Blok Verifikasi Pasca-Push (Post-Push Verification)

Setelah `git push` selesai, lakukan verifikasi independen pada direktori `/tmp/verify` untuk memastikan riwayat di GitHub benar-benar bersih:

Jalankan skrip otomatisasi verifikasi pasca-dorongan:
```bash
./scripts/postpush-verify.sh git@github.com:Visipir/scp.git $(git rev-list --count HEAD)
```
Skrip ini akan mengkloning repositori remote ke direktori terisolasi `/tmp/verify`, memverifikasi kesamaan jumlah commit, memastikan tidak ada berkas sensitif atau password fixture di riwayat, lalu membersihkan `/tmp/verify`. Output yang diharapkan:
`=== OK: Post-push verification PASSED. Remote repository is clean. ===`

---

## Langkah 6: Prosedur Push Repositori iOS (`/root/scp-ios`)

Repositori klien iOS (`/root/scp-ios`) tidak memuat berkas sensitif atau rahasia, sehingga **TIDAK MENGALAMI HISTORY REWRITE**.

```bash
cd /root/scp-ios

# Periksa status: 9 commit ahead of origin/main
git status

# Lakukan push standar (tanpa --force)
git push origin main
```

---

## Langkah 7: Pembaruan Hash Commit

Proses `git-filter-repo` mengubah seluruh SHA commit secara deterministik.
Hash commit live lama (`7ee509f`) berkorespondensi dengan commit bersih `1ff3733` (`1ff373375a389c0dc73594d63d9ff156dc5aeeff`).
Semua dokumentasi selanjutnya wajib merujuk pada hash baru di branch `feat/dark-console-ui` dan `main`.
