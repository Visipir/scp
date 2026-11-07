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

```bash
# 1. Clone fresh dari GitHub ke direktori sementara
rm -rf /tmp/verify
git clone git@github.com:Visipir/scp.git /tmp/verify
cd /tmp/verify

# 2. Periksa bahwa berkas sensitif yang dihapus benar-benar tidak ada di seluruh riwayat git
git log --all --full-history -- "database/database_dump.sql" "sqlite_to_mysql.sql" ".env.testing"
# Output HARUS KOSONG (0 commit)

# 3. Periksa bahwa string rahasia / password fixture tidak ada di seluruh riwayat git
git log --all -S "ChangeMeInitial123" --oneline
# Output HARUS KOSONG (0 commit)

# 4. Periksa bahwa sampel NIS riil di SCP_CONTEXT.md telah digantikan dengan nilai sintetis
git grep "Real Student Imports: NIS like" $(git rev-list --all)
# Output HARUS HANYA: 99010, 99011 (tidak ada NIS 99024 / 99025)

# 5. Konfirmasi total commit count pada remote
git rev-list --all --count
```

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
