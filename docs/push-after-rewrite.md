# Panduan Push Setelah Pembersihan Riwayat Git (History Rewrite)

> [!CAUTION]
> **JANGAN MENJALANKAN `git push` SECARA OTOMATIS.**
> Seluruh perintah di bawah ini harus dijalankan secara manual oleh pemilik repositori setelah memverifikasi bahwa kunci rahasia telah dirotasi dan repositori privat.

---

## Langkah 1: Rotasi Kunci & Konfirmasi Visibilitas Repositori
1. Pastikan Google Gemini API Key yang pernah tercatat di commit awal telah di-revoke / dirotasi via Google AI Studio / Google Cloud Console.
2. Pastikan repositori GitHub `git@github.com:Visipir/scp.git` dalam status **Private**.

---

## Langkah 2: Konfirmasi Remote Origin
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

---

## Langkah 3: Force Push Semua Branch Bersih
Kirim seluruh branch lokal yang telah dibersihkan (`main` dan `feat/dark-console-ui`):
```bash
git push --force-with-lease origin --all
```

---

## Langkah 4: Force Push Semua Tag Bersih
Perbarui seluruh tag (`pre-restyle`):
```bash
git push --force origin --tags
```

---

## Langkah 5: Hubungi GitHub Support (Purge Cache & Pull Request Refs)
Jika repositori pernah berstatus publik atau memiliki fork/pull-request:
1. Buka [GitHub Support Ticket](https://support.github.com/contact).
2. Pilih topik: **Git and GitHub / Removing sensitive data**.
3. Minta pembersihan ref internal dan cache blob:
   > *"Please run `git gc` and purge orphaned unreachable commits, pull request refs, and cached commit views on repository `Visipir/scp` following a sensitive data removal via `git-filter-repo`."*

---

## Langkah 6: Pembaruan di Mesin Lain & Penghapusan Clone Lama
Pada mesin pengembang lain atau server staging:
1. Buat cadangan data lokal yang belum ter-commit (jika ada).
2. Hapus direktori clone lama yang memuat riwayat lama:
   ```bash
   rm -rf /path/to/old/clone
   ```
3. Lakukan clone ulang secara bersih dari origin:
   ```bash
   git clone git@github.com:Visipir/scp.git
   ```

---

## Langkah 7: Catatan Hash Commit
Setelah proses rewrite dengan `git-filter-repo`, seluruh SHA commit dari root commit awal hingga HEAD telah berubah secara deterministik.
Seluruh branch dan tag tetap memelihara struktur tree yang bersih, tanpa ada berkas dump database (`database/database_dump.sql`, `sqlite_to_mysql.sql`) maupun `.env.testing`.
Dokumentasi internal di masa mendatang harus merujuk ke hash commit baru.
