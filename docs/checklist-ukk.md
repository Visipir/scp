# Matriks Bukti Pemenuhan Spesifikasi & Revisi UKK (Checklist UKK)

Dokumen ini memetakan seluruh butir persyaratan Uji Kompetensi Keahlian (UKK) — Spesifikasi Bagian A, Revisi Arahan Penguji (Pak Haris Bagian B), dan Fitur Ekstra — terhadap bukti implementasi kode (`file:function`), nama automated test suite, dan status verifikasi.

---

## Bagian A: Spesifikasi Inti UKK (A1–A7 & Aturan a–e)

| No | Persyaratan | Implementasi (`file:function`) | Pengujian Otomatis (`test name`) | Status |
|---|---|---|---|---|
| **A1** | Otentikasi & RBAC Multi-Peran (Waka, Guru, Kesiswaan/BK/Wali, Siswa) | `app/Http/Controllers/Api/V1/AuthController.php::login`<br>`app/Http/Middleware/RoleMiddleware.php::handle` | `Tests\Feature\Api\V1\AuthTest::test_user_can_login_with_valid_credentials`<br>`Tests\Feature\Api\V1\UserApiTest::test_role_scoping` | **PASS** |
| **A2** | Manajemen Master Jadwal Pelajaran (Putaran 1 & Putaran 2) | `app/Http/Controllers/ScheduleController.php::index`<br>`app/Services/ScheduleService.php::getSchedules` | `Tests\Feature\SchedulePageTest::test_can_view_schedule_index`<br>`Tests\Feature\ScheduleConflictTest::test_schedule_cycle_filtering` | **PASS** |
| **A3** | Pengisian Jurnal Mengajar KBM + Foto + Presensi Siswa | `app/Http/Controllers/JournalController.php::store`<br>`app/Services/JournalService.php::createJournalWithAttendance` | `Tests\Feature\JournalPageTest::test_can_view_journal_index`<br>`Tests\Feature\Api\V1\JournalApiTest::test_teacher_can_create_journal_with_photo` | **PASS** |
| **A4** | Pengajuan Izin Siswa Mandiri + Unggah Berkas Dokumen | `app/Http/Controllers/LeaveRequestController.php::store`<br>`app/Http/Controllers/Api/V1/LeaveRequestController.php::store` | `Tests\Feature\Api\V1\LeaveRequestApiTest::test_student_can_create_leave_request_with_document` | **PASS** |
| **A5** | Verifikasi & Persetujuan Izin Siswa oleh Pihak Berwenang | `app/Http/Controllers/LeaveRequestController.php::updateStatus`<br>`app/Models/LeaveRequest.php::recordDecision` | `Tests\Feature\LeaveDecisionAndWaliMenuD5Test::test_bk_and_wali_both_can_decide_last_decision_is_recorded_and_attendance_syncs` | **PASS** |
| **A6** | Rekapitulasi Presensi Siswa & Ekspor Laporan | `app/Http/Controllers/ReportController.php::attendanceRecap`<br>`app/Http/Controllers/Api/V1/ClassController.php::attendanceRecap` | `Tests\Feature\TeacherClassAccessE2Test::test_regular_non_wali_teacher_full_flow_journal_recap_export_and_403_boundaries` | **PASS** |
| **A7** | Pengajuan & Konfirmasi Tukar Jadwal Antar Guru | `app/Http/Controllers/ScheduleSwapController.php::store`<br>`app/Services/ScheduleSwapService.php::requestSwap` | `Tests\Feature\ScheduleSwapTest::test_teacher_can_request_schedule_swap` | **PASS** |
| **A.a** | Validasi Konflik Guru Ganda, Ruang Ganda, Rombel Ganda | `app/Services/ScheduleService.php::validateConflict`<br>`app/Services/ScheduleService.php::validateSlotConflict` | `Tests\Feature\ScheduleConflictTest::test_cannot_create_conflicting_schedule_for_same_teacher_and_slot` | **PASS** |
| **A.b** | Pembatasan Slot Jam KBM (Senin–Jumat, JP 1–10) | `app/Models/Schedule.php::scopeForPeriod`<br>`app/Services/ScheduleImportService.php` | `Tests\Feature\ScheduleConflictTest::test_period_and_day_boundaries` | **PASS** |
| **A.c** | Kunci Jadwal: Guru Dilarang Edit Saat `is_schedule_locked=true` | `app/Http/Middleware/EnsureScheduleNotLocked.php::handle` | `Tests\Feature\ScheduleLockTest::test_teacher_cannot_create_update_or_delete_schedule_when_locked` | **PASS** |
| **A.d** | Sinkronisasi Otomatis Absensi Jurnal dari Status Izin | `app/Http/Controllers/LeaveRequestController.php::syncAttendanceForLeave`<br>`app/Models/LeaveRequest.php::recordDecision` | `Tests\Feature\LeaveDecisionAndWaliMenuD5Test::test_bk_and_wali_both_can_decide_last_decision_is_recorded_and_attendance_syncs` | **PASS** |
| **A.e** | Peringatan Dashboard 30 Menit Sebelum Kelas Berakhir (Tanpa Cron) | `app/Http/Controllers/DashboardController.php::getUpcomingEndingAlerts` | `Tests\Feature\DashboardTest::test_waka_dashboard_alert_30_min_before_class_end_and_after_end` | **PASS** |

---

## Bagian B: Revisi Arahan Penguji (Pak Haris, B1–B14)

| No | Arahan Revisi | Implementasi (`file:function`) | Pengujian Otomatis (`test name`) | Status |
|---|---|---|---|---|
| **B1** | Skema Relasi Jadwal Mandiri & Integritas Foreign Key | `database/migrations/2026_09_09_115148_add_period_columns_to_schedules_table.php`<br>`app/Models/Schedule.php` | `Tests\Feature\SchedulePageTest::test_can_view_schedule_index` | **PASS** |
| **B2** | Tampilan Jadwal Pelajaran Berdasarkan Putaran 1 & 2 | `resources/views/schedules/index.blade.php`<br>`app/Http/Controllers/ScheduleController.php::index` | `Tests\Feature\SchedulePageTest::test_can_filter_schedule_by_cycle` | **PASS** |
| **B3** | Pemeriksaan Tumpang Tindih Jam Pelajaran (Overlap Exclude Self) | `app/Services/ScheduleService.php::validateConflict` | `Tests\Feature\ScheduleConflictTest::test_update_schedule_allows_saving_same_record_without_self_conflict` | **PASS** |
| **B4** | Dashboard Guru: Banner Jadwal Hari Ini / Pesan Akhir Pekan | `resources/views/dashboard/teacher.blade.php`<br>`app/Http/Controllers/DashboardController.php::teacherDashboard` | `Tests\Feature\WeekendEmptyScheduleJ2Test::test_j2_saturday_and_sunday_never_500_and_show_clear_empty_schedule_message` | **PASS** |
| **B5** | Form Jurnal Mengajar dengan Unggah Foto Kompresi | `resources/views/journals/create.blade.php`<br>`app/Http/Controllers/JournalController.php::store` | `Tests\Feature\JournalPageTest::test_can_view_journal_index` | **PASS** |
| **B6** | Presensi 5 Status Siswa (Hadir, Sakit, Izin, Alpa, Dispen) | `resources/views/journals/create.blade.php`<br>`app/Models/Attendance.php` | `Tests\Feature\Api\V1\JournalApiTest::test_teacher_can_create_journal_with_photo` | **PASS** |
| **B7** | Formulir Izin Siswa dengan Unggah Surat Keterangan (PDF/Foto) | `resources/views/leave-requests/create.blade.php`<br>`app/Http/Controllers/LeaveRequestController.php::store` | `Tests\Feature\Api\V1\LeaveRequestApiTest::test_student_can_create_leave_request_with_document` | **PASS** |
| **B8** | Verifikasi Izin Siswa oleh Wali Kelas & Guru BK | `resources/views/leave-requests/show.blade.php`<br>`app/Http/Controllers/LeaveRequestController.php::updateStatus` | `Tests\Feature\LeaveDecisionAndWaliMenuD5Test::test_wali_sees_all_leave_statuses_with_filter_and_dashboard_menus` | **PASS** |
| **B9** | Sinkronisasi Presensi Saat Jurnal Baru Dibuat Pasca-Izin | `app/Services/JournalService.php::createJournalWithAttendance`<br>`app/Models/LeaveRequest.php` | `Tests\Feature\LeaveDecisionAndWaliMenuD5Test::test_bk_and_wali_both_can_decide_last_decision_is_recorded_and_attendance_syncs` | **PASS** |
| **B10** | Alur Permohonan & Konfirmasi Tukar Jadwal Pelajaran | `resources/views/schedules/swaps/index.blade.php`<br>`app/Http/Controllers/ScheduleSwapController.php` | `Tests\Feature\ScheduleSwapTest::test_teacher_can_request_schedule_swap` | **PASS** |
| **B11** | Akses Rekap Presensi Harian Kelas untuk Guru Pengajar | `app/Http/Controllers/ReportController.php::attendanceRecap`<br>`app/Models/User.php::authorizedClassIds` | `Tests\Feature\TeacherClassAccessE2Test::test_regular_non_wali_teacher_full_flow_journal_recap_export_and_403_boundaries` | **PASS** |
| **B12** | Ekspor Laporan Presensi ke Berkas CSV Bersih | `app/Http/Controllers/ReportController.php::exportAttendanceRecapCsv` | `Tests\Feature\TeacherClassAccessE2Test::test_regular_non_wali_teacher_full_flow_journal_recap_export_and_403_boundaries` | **PASS** |
| **B13** | Fitur Kunci / Buka Jadwal oleh Waka Kurikulum via Switch GUI | `resources/views/waka/settings.blade.php`<br>`app/Http/Controllers/SystemConfigurationController.php::update` | `Tests\Feature\ScheduleLockTest::test_waka_can_toggle_schedule_lock` | **PASS** |
| **B14** | Desain Antarmuka Konsol Minimalis (Responsif Desktop & Mobile) | `resources/css/app.css`<br>`resources/views/layouts/app.blade.php` | `Tests\Feature\UiConsistencyTest::test_waka_can_access_all_12_required_pages_with_uniform_titles` | **PASS** |

---

## Bagian C: Fitur Ekstra & Pengerasan Keamanan (Production Hardening)

| No | Fitur Tambahan | Implementasi (`file:function`) | Pengujian Otomatis (`test name`) | Status |
|---|---|---|---|---|
| **E1** | Split-Screen Login 2-Kolom + Kustomisasi Background Waka | `resources/views/auth/login.blade.php`<br>`app/Http/Controllers/SystemConfigurationController.php::updateLoginBackground` | `Tests\Feature\UiConsistencyTest::test_guest_auth_pages_render_cleanly` | **PASS** |
| **E2** | Impor Pegawai, Wali/BK, & Jadwal (Pratinjau & Komit 2 Tahap) | `app/Services/EmployeeImportService.php`<br>`app/Services/HomeroomImportService.php`<br>`app/Services/ScheduleImportService.php` | `Tests\Feature\EmployeeImportTest::test_waka_can_preview_and_commit_employee_import`<br>`Tests\Feature\FuzzyMatchingDisciplineTest::test_exact_and_fuzzy_discipline` | **PASS** |
| **E3** | Cakupan Hak Akses Kelas Wali Kelas & Guru BK | `app/Models/User.php::supervisedClassIds`<br>`app/Http/Middleware/EnsureSupervisedClassAccess.php` | `Tests\Feature\WaliKelasLeaveScopeVerificationTest::test_wali_kelas_a_cannot_view_or_approve_leave_of_class_b_on_web_and_api` | **PASS** |
| **E4** | Menu Cepat Navigasi Perwalian pada Dashboard Guru Wali | `resources/views/dashboard/teacher.blade.php` (Card Kelas Perwalian) | `Tests\Feature\LeaveDecisionAndWaliMenuD5Test::test_wali_sees_all_leave_statuses_with_filter_and_dashboard_menus` | **PASS** |
| **E5** | Dukungan Peran Ganda Guru Pengajar Sekaligus Wali Kelas | `app/Models/User.php::isHomeroomTeacher`<br>`app/Models/User.php::allAccessibleClassIds` | `Tests\Feature\TeacherClassAccessE2Test::test_homeroom_teacher_plus_teaching_other_class_has_combined_access` | **PASS** |
| **E6** | Perlindungan Kekebalan 5 Akun Terlindung Penguji | `config/protected_accounts.php`<br>`app/Models/User.php::isProtectedAccount` | `Tests\Feature\ProtectedAccountsF1Test::test_config_and_is_protected_account_identify_all_five_accounts`<br>`Tests\Feature\ProtectedAccountsScopeAfterImportF3Test::test_f3_all_five_protected_accounts_retain_exact_post_import_scopes` | **PASS** |
| **E7** | Sanitasi Input Login & Perlindungan Rate Limiter Terhadap Objek | `app/Http/Requests/Auth/LoginRequest.php`<br>`app/Http/Requests/Api/V1/LoginRequest.php` | `Tests\Feature\LoginValidationJ1Test::test_j1_array_input_on_web_and_api_login_returns_422_not_500` | **PASS** |
