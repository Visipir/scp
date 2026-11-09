# Web vs API Parity Audit (SCP – School Class Progress)

Dokumen ini memetakan seluruh rute web terotentikasi (`routes/web.php`) terhadap rute API v1 (`routes/api.php`) untuk mencapai paritas fitur mobile penuh pada aplikasi iOS native.

## 0. Pemeriksaan Nginx (`/etc/nginx/sites-available/your-app`)
- **Status `client_max_body_size`:** **SUDAH TERKONFIGURASI** (`client_max_body_size 50m;` baris 15).
- **FastCGI PHP:** `upload_max_filesize=50M` dan `post_max_size=55M` (baris 30-31).
- **Catatan:** Siap melayani unggah berkas materi pembelajaran/ujian hingga 50 MB (51.200 KB) tanpa perubahan konfigurasi web server.

---

## 1. Tabel Audit Paritas Rute Web vs API v1

| Fitur Web | Rute Web (Method & URI) | Controller@Method | Middleware Role | Status API v1 | Tindakan / Endpoint Target |
|---|---|---|---|---|---|
| **Dashboard** | `GET /dashboard` | `DashboardController` | `auth` (1,2,3,4) | Ada | `GET /api/v1/dashboard` (Sudah ada) |
| **Profil Saya** | `GET /profile` | `ProfileController@edit` | `auth` (1,2,3,4) | Ada | `GET /api/v1/auth/me` (Sudah ada) |
| **Ubah Profil** | `PATCH /profile` | `ProfileController@update` | `auth` (1,2,3,4) | Ada | `PATCH /api/v1/auth/profile` (Sudah ada) |
| **Hapus Akun Sendiri** | `DELETE /profile` | `ProfileController@destroy` | `auth` (1,2,3,4) | Tidak ada | **Sengaja tidak ditambahkan** (Akun sekolah tidak boleh dihapus mandiri oleh siswa/guru via mobile). |
| **Ubah Kata Sandi** | `PUT /password` | `PasswordController@update` | `auth` (1,2,3,4) | Ada | `POST /api/v1/auth/change-password` (Sudah ada) |
| **Paksa Ubah Sandi** | `POST /first-change-password` | `ForcePasswordChangeController@update` | `auth` (1,2,3,4) | Ada | Di-handle via middleware `EnsureApiPasswordIsChanged` + `POST /api/v1/auth/change-password`. |
| **Logout** | `POST /logout` | `AuthenticatedSessionController@destroy` | `auth` (1,2,3,4) | Ada | `POST /api/v1/auth/logout` (Sudah ada) |
| **Chatbot AI** | `POST /chat/message` | `ChatController@sendMessage` | `auth` (1,2,3,4) | Tidak ada | **Tambahkan:** `POST /api/v1/chat/message` |
| **Konfigurasi Sistem** | `GET /system-configuration` | `SystemConfigurationController@edit` | `role:1` | Ada | `GET /api/v1/config` (Sudah ada) |
| **Toggle Putaran** | `PATCH /system-configuration/toggle-cycle` | `SystemConfigurationController@toggleCycle` | `role:1` | Ada | `PATCH /api/v1/system/cycle` (Sudah ada) |
| **Toggle Kunci Jadwal** | `PATCH /system-configuration/toggle-lock` | `SystemConfigurationController@toggleLock` | `role:1` | Ada | `PATCH /api/v1/system/lock` (Sudah ada) |
| **Daftar Jurnal Kosong** | (Tampil di dashboard/settings web) | `SystemConfigurationController` | `role:1` | Ada | `GET /api/v1/system/unfilled-journals` (Sudah ada) |
| **Pengaturan Gemini** | `POST /waka/settings/gemini` | `SystemConfigurationController@updateGeminiConfig` | `role:1` | Tidak ada | **Sengaja tidak ditambahkan** (Pengaturan API key AI khusus desktop admin). |
| **Impor Jadwal PDF** | `POST /waka/schedules/import-pdf` | `ScheduleImportController@import` | `role:1` | Tidak ada | **Sengaja tidak ditambahkan** (Pemrosesan PDF OCR/Gemini khusus desktop admin). |
| **AI Importer Tool** | `POST /ai-importer/process`, `POST /ai-importer/store` | `AiImporterController` | `role:1` | Tidak ada | **Sengaja tidak ditambahkan** (Fitur backend batch parsing dokumen web admin). |
| **Daftar Kelas** | `GET /classes` | `SchoolClassController@index` | `role:1,3` | Parsial | **Perluas:** `GET /api/v1/classes` (Saat ini hanya lookup sederhana, perlu filter tahun ajaran, tingkat, jurusan, dan hitungan siswa dengan scoping Wali Kelas). |
| **Tambah Kelas** | `POST /classes` | `SchoolClassController@store` | `role:1,3` | Tidak ada | **Tambahkan:** `POST /api/v1/classes` |
| **Detail Kelas** | `GET /classes/{class}` | `SchoolClassController@show` | `role:1,3` | Tidak ada | **Tambahkan:** `GET /api/v1/classes/{class}` (Daftar siswa, statistik kehadiran kelas, riwayat ketidakhadiran, scoping Wali Kelas). |
| **Perbarui Kelas** | `PUT /classes/{class}` | `SchoolClassController@update` | `role:1,3` | Tidak ada | **Tambahkan:** `PUT /api/v1/classes/{class}` |
| **Hapus Kelas** | `DELETE /classes/{class}` | `SchoolClassController@destroy` | `role:1,3` | Tidak ada | **Tambahkan:** `DELETE /api/v1/classes/{class}` (Cek relasi siswa & jadwal). |
| **Rekap Presensi Kelas (JSON)** | `GET /classes/{class}` (Parameter bulan/tanggal) | `SchoolClassController@show` | `role:1,3` | Tidak ada | **Tambahkan:** `GET /api/v1/classes/{class}/attendance` (`?month=YYYY-MM`, scoping Wali Kelas). |
| **Ekspor CSV Presensi** | `GET /classes/{class}/export` | `SchoolClassController@exportAttendance` | `role:1,3` | Tidak ada | **Tambahkan:** `GET /api/v1/classes/{class}/export` (`?start_date=...&end_date=...`, streaming CSV). |
| **Impor Siswa Excel/PDF** | `POST /classes/import-students` | `StudentImportController@import` | `role:1,3` | Tidak ada | **Sengaja tidak ditambahkan** (Impor massal roster ribuan siswa khusus desktop admin). |
| **Daftar Pengguna** | `GET /users` | `UserController@index` | `role:1,3` | Tidak ada | **Tambahkan:** `GET /api/v1/users` (Filter `role_id`, pencarian `search`, paginasi). |
| **Detail Pengguna** | `GET /users/{user}/edit` | `UserController@edit` | `role:1,3` | Tidak ada | **Tambahkan:** `GET /api/v1/users/{user}` |
| **Tambah Pengguna** | `POST /users` | `UserController@store` | `role:1,3` | Tidak ada | **Tambahkan:** `POST /api/v1/users` (Validasi role, hash password, default `must_change_password=1`). |
| **Perbarui Pengguna** | `PUT /users/{user}` | `UserController@update` | `role:1,3` | Tidak ada | **Tambahkan:** `PUT /api/v1/users/{user}` |
| **Hapus Pengguna** | `DELETE /users/{user}` | `UserController@destroy` | `role:1,3` | Tidak ada | **Tambahkan:** `DELETE /api/v1/users/{user}` (Cegah hapus diri sendiri & cek dependensi). |
| **Reset Sandi Pengguna** | `PATCH /users/{user}/reset-password` | `UserController@resetPassword` | `role:1,3` | Tidak ada | **Tambahkan:** `PATCH /api/v1/users/{user}/reset-password` (Reset password = username + `must_change_password=1`). |
| **Jadwal Pelajaran** | `GET /schedules` | `ScheduleController@index` | `role:1,2` | Ada | `GET /api/v1/schedules` (Sudah ada) |
| **Tambah Jadwal** | `POST /schedules` | `ScheduleController@store` | `role:1,2` | Ada | `POST /api/v1/schedules` (Sudah ada) |
| **Perbarui Jadwal** | `PUT /schedules/{schedule}` | `ScheduleController@update` | `role:1,2` | Ada | `PUT /api/v1/schedules/{schedule}` (Sudah ada) |
| **Hapus Jadwal** | `DELETE /schedules/{schedule}` | `ScheduleController@destroy` | `role:1,2` | Ada | `DELETE /api/v1/schedules/{schedule}` (Sudah ada) |
| **Jurnal Mengajar** | `GET /journals` | `JournalController@index` | `role:1,2,3` | Ada | `GET /api/v1/journals` (Sudah ada) |
| **Prepare Jurnal** | `GET /journals/create` | `JournalController@create` | `role:2` | Ada | `GET /api/v1/journals/prepare` (Sudah ada) |
| **Simpan Jurnal** | `POST /journals` | `JournalController@store` | `role:2` | Ada | `POST /api/v1/journals` (Sudah ada) |
| **Detail Jurnal** | `GET /journals/{journal}` | `JournalController@show` | `role:1,2,3` | Ada | `GET /api/v1/journals/{journal}` (Sudah ada) |
| **Foto Jurnal** | `GET /journals/{journal}/photo` | `JournalController@photo` | `role:1,2,3` | Ada | `GET /api/v1/journals/{journal}/photo` (Sudah ada) |
| **Update Presensi Jurnal**| `PUT /attendances/{journal}` | `AttendanceController@update` | `role:2` | Ada | `PUT /api/v1/journals/{journal}/attendances` (Sudah ada) |
| **Presensi Siswa** | `GET /attendance` | `StudentAttendanceController@index` | `role:4` | Ada | `GET /api/v1/attendance` (Sudah ada) |
| **Daftar Izin** | `GET /leave-requests` | `LeaveRequestController@index` | `role:1,3,4` | Ada | `GET /api/v1/leave-requests` (Sudah ada) |
| **Ajukan Izin** | `POST /leave-requests` | `LeaveRequestController@store` | `role:4` | Ada | `POST /api/v1/leave-requests` (Sudah ada) |
| **Detail Izin** | `GET /leave-requests/{leaveRequest}` | `LeaveRequestController@show` | `role:1,3,4` | Ada | `GET /api/v1/leave-requests/{leaveRequest}` (Sudah ada) |
| **Unduh Dokumen Izin** | `GET /leave-requests/{leaveRequest}/document` | `LeaveRequestController@document` | `role:1,3,4` | Ada | `GET /api/v1/leave-requests/{leaveRequest}/document` (Sudah ada) |
| **Verifikasi Izin** | `PATCH /leave-requests/{leaveRequest}/status` | `LeaveRequestController@updateStatus` | `role:1,3` | Ada | `PATCH /api/v1/leave-requests/{leaveRequest}/status` (Sudah ada) |
| **Jadwal Ruangan (List)**| `GET /room-schedules` | `RoomScheduleController@index` | `role:1,2,3` | Tidak ada | **Tambahkan:** `GET /api/v1/room-schedules` (Filter hari, putaran, kelas, matriks jam pelajaran). |
| **Tambah Jadwal Ruang**| `POST /room-schedules` | `RoomScheduleController@store` | `role:1,2,3` | Tidak ada | **Tambahkan:** `POST /api/v1/room-schedules` (Validasi jam & cek bentrok ruangan). |
| **Hapus Jadwal Ruang** | `DELETE /room-schedules/{roomSchedule}` | `RoomScheduleController@destroy` | `role:1,2,3` | Tidak ada | **Tambahkan:** `DELETE /api/v1/room-schedules/{roomSchedule}` |
| **Impor Jadwal Ruangan**| `POST /room-schedules/import` | `RoomScheduleController@import` | `role:1,2,3` | Tidak ada | **Sengaja tidak ditambahkan** (Parsing PDF web admin). |
| **Kalender Akademik** | `GET /academic-calendar` | `AcademicCalendarController@index` | `role:1,3` | Tidak ada | **Tambahkan:** `GET /api/v1/academic-calendar` (Daftar agenda/libur, status KBM efektif/libur). |
| **Tambah Agenda Kalender**| `POST /academic-calendar` | `AcademicCalendarController@store` | `role:1,3` | Tidak ada | **Tambahkan:** `POST /api/v1/academic-calendar` |
| **Hapus Agenda Kalender** | `DELETE /academic-calendar/{academicCalendar}` | `AcademicCalendarController@destroy` | `role:1,3` | Tidak ada | **Tambahkan:** `DELETE /api/v1/academic-calendar/{academicCalendar}` |
| **Impor CSV Kalender** | `POST /academic-calendar/import` | `AcademicCalendarController@import` | `role:1,3` | Tidak ada | **Sengaja tidak ditambahkan** (Impor CSV web admin). |
| **Daftar Hari Tidak Efektif** | `GET /ineffective-days` | `IneffectiveDayController@index` | `role:1,3` | Tidak ada | **Tambahkan:** `GET /api/v1/ineffective-days` (Daftar hari libur/tidak efektif KBM non-kalender atau seluruh catatan `IneffectiveDay`; pada web di-redirect ke kalender akademik, namun pada API v1 disediakan endpoint list JSON mandiri). |
| **Tambah Hari Tidak Efektif** | `POST /ineffective-days` | `IneffectiveDayController@store` | `role:1,3` | Tidak ada | **Tambahkan:** `POST /api/v1/ineffective-days` (Penetapan satu hari atau rentang tanggal hari tidak efektif). |
| **Hapus Hari Tidak Efektif** | `DELETE /ineffective-days/{ineffectiveDay}` | `IneffectiveDayController@destroy` | `role:1,3` | Tidak ada | **Tambahkan:** `DELETE /api/v1/ineffective-days/{ineffectiveDay}` (Menghapus catatan hari tidak efektif tertentu). |
| **Materi & Ujian (List)** | `GET /exams` | `ExamController@index` | `auth` (1,2,3,4) | Tidak ada | **Tambahkan:** `GET /api/v1/exams` (Kategori: materi, bank_soal, ujian; filter kelas, mapel; scoping Siswa/Guru/Waka). |
| **Detail Materi & Ujian** | `GET /exams/{exam}` | `ExamController@show` | `auth` (1,2,3,4) | Tidak ada | **Tambahkan:** `GET /api/v1/exams/{exam}` (Scoping siswa sesuai kelas). |
| **Tambah Materi & Ujian** | `POST /exams` | `ExamController@store` | `role:1,2` | Tidak ada | **Tambahkan:** `POST /api/v1/exams` (Broadcast ke `class_ids`, berkas maks 51.200 KB, link eksternal). |
| **Perbarui Materi/Ujian** | `PUT /exams/{exam}` | `ExamController@update` | `role:1,2` | Tidak ada | **Tambahkan:** `PUT /api/v1/exams/{exam}` (Waka atau guru pemilik). |
| **Hapus Materi/Ujian** | `DELETE /exams/{exam}` | `ExamController@destroy` | `role:1,2` | Tidak ada | **Tambahkan:** `DELETE /api/v1/exams/{exam}` (Waka atau guru pemilik, hapus berkas penyimpanan). |
| **Unduh Berkas Materi** | `GET /exams/{exam}/download` | `ExamController@download` | `auth` (1,2,3,4) | Tidak ada | **Tambahkan:** `GET /api/v1/exams/{exam}/download` (Streaming berkas lampiran dengan otentikasi). |
| **Verifikasi Email / Auth Web** | `verify-email*`, `confirm-password*` | `Auth\*` | `auth` | Tidak ada | **Sengaja tidak ditambahkan** (Alur session-based Breeze web; mobile menggunakan token Sanctum). |

---

## 2. Ringkasan Kesenjangan (Gaps) yang Wajib Diimplementasikan

Sesuai instruksi dan audit di atas, fitur yang akan diimplementasikan ke API v1 dibagi menjadi beberapa batch logis:

1. **Task 1: Manajemen Pengguna (`Users CRUD` & `Reset Password`)**
   - Endpoints: `GET /api/v1/users`, `POST /api/v1/users`, `GET /api/v1/users/{user}`, `PUT /api/v1/users/{user}`, `DELETE /api/v1/users/{user}`, `PATCH /api/v1/users/{user}/reset-password`
   - Ekstraksi `UserService` agar dipakai bersama oleh `UserController` (web) dan `Api\V1\UserController`.
   - Middleware: `role:1,3`. Scoping & validasi: cegah hapus diri sendiri, hash password, `must_change_password=1` untuk pengguna baru/reset, aturan `class_id` untuk role 4 (Siswa) dan role 3 (Wali Kelas).
   
2. **Task 2: Manajemen Kelas, Siswa per Kelas, & Rekap Presensi (`Classes CRUD & Recap/Export`)**
   - Endpoints:
     - `GET /api/v1/classes` (Daftar kelas lengkap dengan paginasi, filter tahun ajaran/tingkat/jurusan, `students_count`, tetap kompatibel dengan format lookup sederhana).
     - `POST /api/v1/classes`
     - `GET /api/v1/classes/{class}` (Detail kelas beserta daftar siswa dan agregat presensi kelas).
     - `PUT /api/v1/classes/{class}`
     - `DELETE /api/v1/classes/{class}` (Cek integritas relasi siswa & jadwal).
     - `GET /api/v1/classes/{class}/attendance` (Rekap presensi bulanan JSON `?month=YYYY-MM` dengan scoping Wali Kelas).
     - `GET /api/v1/classes/{class}/export` (Streaming file CSV rekap presensi tanggal mulai s/d selesai persis seperti web).
   - Ekstraksi `SchoolClassService` untuk web dan API.
   - Middleware: `role:1,3` dengan penegakan `User::scopedClassId()`.

3. **Task 3: Materi Pembelajaran, Bank Soal, dan Ujian (`Exams/Materials`)**
   - Endpoints:
     - `GET /api/v1/exams` (Filter `category`, `class_id`, `subject`, `search`, `sort`, `view_scope`).
     - `POST /api/v1/exams` (Broadcast ke banyak `class_ids`, upload `material_file` maks 51.200 KB, link eksternal).
     - `GET /api/v1/exams/{exam}` (Scoping siswa ke kelasnya).
     - `PUT /api/v1/exams/{exam}` (Waka role 1 atau guru pemilik role 2).
     - `DELETE /api/v1/exams/{exam}` (Waka atau guru pemilik, penghapusan file otomatis).
     - `GET /api/v1/exams/{exam}/download` (Streaming download dengan verifikasi hak akses).
   - Ekstraksi `ExamService` untuk web dan API.
   - Middleware: `auth:sanctum` untuk index/show/download; `role:1,2` untuk store/update/destroy.

4. **Task 4: Jadwal Ruangan & Kalender Akademik (`Room Schedules & Academic Calendar`)**
   - Endpoints:
     - `GET /api/v1/room-schedules`, `POST /api/v1/room-schedules`, `DELETE /api/v1/room-schedules/{roomSchedule}` (`role:1,2,3`).
     - `GET /api/v1/academic-calendar`, `POST /api/v1/academic-calendar`, `DELETE /api/v1/academic-calendar/{academicCalendar}` (`role:1,3`).
   - Ekstraksi service terkait.

5. **Task 5: Asisten Chat AI (`Ai Chatbot`)**
   - Endpoint: `POST /api/v1/chat/message` (`auth:sanctum`, memanggil `AiChatbotService`).

6. **Task 6: Dokumentasi OpenAPI 3.1 & API.md**
   - Memperbarui `docs/openapi.yaml` dan `docs/API.md` mencakup seluruh skema request, response, error, dan otorisasi dari endpoint baru.
