# SCP API (v1)

Base URL: `/api/v1` (e.g. `http://localhost/api/v1`). The legacy `POST /api/login`
endpoint has been decommissioned and returns **`410 Gone`**; `POST /api/v1/auth/login`
is the supported login endpoint. Legacy `/api/me` and `/api/logout` remain deprecated.
The full contract lives in [`openapi.yaml`](./openapi.yaml).

## Authentication

Stateless Bearer tokens via Laravel Sanctum. Tokens expire after **43200 minutes** (30 days).

1. `POST /api/v1/auth/login` with `{username, password, device_name?}`.
2. Use the returned `data.token` as `Authorization: Bearer <token>`.
3. `POST /api/v1/auth/logout` revokes the current token.

Rate limiting:
- Login (`POST /api/v1/auth/login` and web `POST /login`): **5 attempts per minute per username + IP** (`429` on excess).
- Password reset & email verification (`POST /api/v1/auth/reset-password`, `POST /api/reset-password`, `POST /reset-password`, `POST /reset-password-token`, `POST /api/v1/auth/email/verify`, `POST /api/v1/profile/email/verify`, `POST /profile/email/verify`): **6 attempts per minute per IP** (`throttle:6,1`, `429` on the 7th request).

### Login (curl)

```bash
curl -s -X POST http://localhost/api/v1/auth/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"username":"guru_andi","password":"password","device_name":"iPhone 15"}'
```

### Authenticated request (curl)

```bash
curl -s http://localhost/api/v1/config \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer $TOKEN"
```

## `must_change_password` flow

Users created by the school may have `must_change_password = 1`.

- `POST /api/v1/auth/login` still succeeds and returns `data.must_change_password: true`.
- While the flag is set, every endpoint **except** `auth/login`, `auth/logout`,
  `auth/me` and `auth/change-password` returns `403`:

  ```json
  {"message":"Anda harus mengganti kata sandi terlebih dahulu.","errors":null,"code":"must_change_password"}
  ```

- `POST /api/v1/auth/change-password` does **not** require `current_password` while
  the flag is set (it does afterwards), and clears the flag.

```bash
curl -s -X POST http://localhost/api/v1/auth/change-password \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"password":"rahasia123","password_confirmation":"rahasia123"}'
```

## Response envelopes

- Single resource: `{"data": {...}}`
- List: `{"data": [...]}`
- Paginated list: `{"data": [...], "meta": {"current_page","per_page","total","last_page"}}`
  (`?page=`, `?per_page=` max 50, default 15)

Dates are `YYYY-MM-DD`, times `HH:mm`, datetimes ISO-8601 with offset
(timezone `Asia/Jakarta`). All keys are `snake_case`.

## Errors

Every failure uses the same envelope:

```json
{"message": "string", "errors": {"field": ["message"]} | null}
```

| Status | Meaning | Example message |
|--------|---------|-----------------|
| 401 | Missing/invalid token, bad credentials | `Unauthenticated.` / `Username atau password salah.` |
| 403 | Not allowed / password change required / schedule locked | `Akses ditolak.` |
| 404 | Not found or no file | `Data tidak ditemukan.` |
| 410 | Legacy endpoint decommissioned | `Endpoint legacy /api/login telah dinonaktifkan (410 Gone). Gunakan /api/v1/auth/login.` |
| 422 | Validation error (field keys included) | `Data yang diberikan tidak valid.` |
| 429 | Rate limited | `Terlalu banyak percobaan. Silakan coba lagi nanti.` |
| 500 | Server error (generic in production) | `Terjadi kesalahan pada server.` |

## Private files

Journal photos and leave documents live on the private disk. The API returns
authenticated URLs (`photo_url`, `document_url`) that must be fetched with the
Bearer token; they are never public `/storage/...` paths.

## Multipart examples

### Journal upload (Guru)

```bash
curl -s -X POST http://localhost/api/v1/journals \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -F schedule_id=12 \
  -F date=2026-10-17 \
  -F topic_description='Materi: Perulangan' \
  -F photo=@evidence.jpg \
  -F 'absent_students[0][student_id]=31' \
  -F 'absent_students[0][status]=Sakit' \
  -F 'absent_students[0][notes]=Surat dokter'
```

Response `201` includes `attendance_summary`. Creating a journal also syncs
attendance for the whole class (default `Hadir`, approved leave → `Izin`).
A duplicate journal (same schedule + date) returns `422` on field `date`.

### Leave document upload (Siswa/Ortu)

```bash
curl -s -X POST http://localhost/api/v1/leave-requests \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -F student_id=31 \
  -F start_date=2026-10-18 \
  -F end_date=2026-10-19 \
  -F reason='Demam' \
  -F document=@surat.pdf
```

Parents may only submit for their own children (`403` otherwise). New requests
start as `Pending`.

## Roles & Scoping

`role_id`:
- `1` Waka Kurikulum (Full administrative access across all endpoints)
- `2` Guru (Subject teacher: journals, materials/exams, room bookings)
- `3` Tim Kesiswaan (Wali Kelas / BK). A role 3 account with `class_id` is scoped strictly to that homeroom class (`User::scopedClassId`); without `class_id` is Guru BK and sees all classes. In `/users`, role 3 can only manage role 4 (Siswa/Ortu).
- `4` Siswa / Orang Tua (Students & parents: view schedules, attendance, materials, submit leave requests).

---

## Users (`/api/v1/users`)

Access:
- **Role 1**: Full CRUD across all roles.
- **Role 3**: Can only list, create, update, delete, and reset password for **role 4 (Siswa/Ortu)**. Attempting to manage roles 1–3 returns `403`.
- Self-deletion is forbidden (`422`).
- Deleting users with dependent records (e.g. schedules, journals, classes) is forbidden (`422`).
- New users receive `must_change_password = 1`.

### Endpoints
- `GET /api/v1/users?role_id=&search=&page=&per_page=` — List paginated users.
- `POST /api/v1/users` — Create user (`username`, `fullname`, `role_id`, `password`, optional `phone_number`, `class_id`, `parent_id`, `gender`, `attendance_number`).
- `GET /api/v1/users/{id}` — Get user details (including parent and children relationships).
- `PUT /api/v1/users/{id}` — Update user fields.
- `DELETE /api/v1/users/{id}` — Delete user.
- `PATCH /api/v1/users/{id}/reset-password` — Reset password to a temporary password and set `must_change_password = true`.

---

## Classes & Attendance Recap (`/api/v1/classes`)

Access:
- **Role 1**: Full CRUD.
- **Role 3**: Read-only access to own class (Wali Kelas scoping). Attempting to access another class returns `403` or `404`.
- **Role 2 & 4**: Forbidden.

### Endpoints
- `GET /api/v1/classes?search=&page=&per_page=` — List classes (scoped for Wali Kelas).
- `POST /api/v1/classes` — Create class (`class_name`, `academic_year`, `grade_level`, `major`).
- `GET /api/v1/classes/{id}` — Show class details along with enrolled students list.
- `PUT /api/v1/classes/{id}` — Update class details.
- `DELETE /api/v1/classes/{id}` — Delete class (fails with `422` if classes have students or schedules).
- `GET /api/v1/classes/{id}/attendance?month=YYYY-MM` — Monthly attendance recap JSON (Hadir, Sakit, Izin, Alpa counts per student).
- `GET /api/v1/classes/{id}/export?month=YYYY-MM` — Download monthly attendance recap as Excel (`.xlsx`) stream.

---

## Learning Materials, Exams & Bank Soal (`/api/v1/exams`)

File attachments are stored privately and streamed via authenticated endpoints.
- Max file size: **50 MB** (`51200` KB).
- Allowed MIME types: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, JPG, PNG.
- Nginx & PHP are configured with `client_max_body_size 50m`.

Access:
- **Role 1**: View all, create, update, delete, download any.
- **Role 2 (Guru)**: View all, create, update/delete own uploads, download.
- **Role 3 & 4**: View and download materials assigned to their class (bank soal is hidden from role 4).

### Endpoints
- `GET /api/v1/exams?type=&class_id=&search=&page=&per_page=` — List materials/exams.
- `POST /api/v1/exams` — Upload material/exam (`multipart/form-data`: `title`, `type`, `class_id`, `subject`, `description`, `file`).
- `GET /api/v1/exams/{id}` — View material/exam metadata.
- `PUT /api/v1/exams/{id}` — Update metadata (`title`, `type`, `class_id`, `subject`, `description`).
- `DELETE /api/v1/exams/{id}` — Delete record and remove attachment from storage.
- `GET /api/v1/exams/{id}/download` — Stream download file (`Accept: application/octet-stream` with Bearer token).

---

## Room Booking (`/api/v1/room-schedules`)

Allows booking school rooms/laboratories without schedule conflicts.

Access:
- **Role 1 & 2**: Create booking, view.
- **Role 3 & 4**: View only.
- Delete: Creator or Role 1 only.

### Endpoints
- `GET /api/v1/room-schedules?date=YYYY-MM-DD&room_id=` — List room schedules.
- `POST /api/v1/room-schedules` — Book room (`room_id`, `date`, `start_time`, `end_time`, `purpose`). Fails on time overlap with existing booking (`422`).
- `DELETE /api/v1/room-schedules/{id}` — Delete booking.

---

## Academic Calendar (`/api/v1/academic-calendar`)

School agenda and academic calendar events.

Access:
- **Role 1**: Create and delete events.
- **Role 2, 3, 4**: Read-only.

### Endpoints
- `GET /api/v1/academic-calendar?type=&start_date=&end_date=` — List calendar events.
- `POST /api/v1/academic-calendar` — Create event (`title`, `description`, `start_date`, `end_date`, `type`: `kbm|libur|ujian|kegiatan|lainnya`).
- `DELETE /api/v1/academic-calendar/{id}` — Delete calendar event.

---

## Ineffective Days (`/api/v1/ineffective-days`)

Days marked as non-effective school days (holidays, special off-days) for attendance calculations.

Access:
- **Role 1**: Create and delete.
- **Role 2, 3, 4**: Read-only.

### Endpoints
- `GET /api/v1/ineffective-days?year=YYYY&month=M` — List ineffective days.
- `POST /api/v1/ineffective-days` — Create entry (`date`, `description`).
- `DELETE /api/v1/ineffective-days/{id}` — Delete ineffective day.

---

## AI Assistant Chat (`/api/v1/chat/message`)

Integrates Google Gemini AI Assistant with contextual school data awareness via server-side read-only tool calling.

### Privacy & Data Minimization
- **External Provider Transmission**: To answer questions regarding personal school data (e.g. absences, teaching schedules, unfilled journals), the server executes scoped read-only tools and sends the minimal school data (status counts, schedule slots, journal status, leave approval status, exam titles/dates) to Google Gemini.
- **Strict PII Protection**: No passwords, usernames/NIS, email addresses, phone numbers, tokens, internal database IDs, or file paths are ever transmitted to the AI provider.
- **Telemetry Logging**: Request bodies, user messages, tool arguments, tool results, and assistant replies are never logged. The application records only operational telemetry metrics (user ID, tool call rounds, latency in milliseconds, and status code).

### Security & Rate Limiting
- **Identity Scope**: User identity is derived strictly from the Sanctum bearer token (`$request->user()`). Identity fields passed in request bodies or message prompts are completely ignored.
- **Prompt Injection Defense**: User-written content (leave reasons, exam descriptions, journal notes) is enclosed within `[DATA: ...]` tags. Tools are strictly read-only and cannot mutate data.
- **Throttling**: Dual-tier limiter per authenticated user:
  - Max **10 requests per minute**
  - Max **100 requests per day**
  - Exceeding limit returns `429 Too Many Requests` with Indonesian explanation.
- **Input Validation & Formatting**: User message max length **1,000 characters**; conversation history max **20 messages**; total timeout **45 seconds**; max **3 tool rounds** per message. Output is delivered in clean plain text with all markdown syntax stripped.
- **Credentials**: Gemini API key is passed securely via `x-goog-api-key` header (never exposed in URL query parameters).

### Server-Side Read-Only Tools
1. `get_attendance_summary`: Monthly/all-time attendance counts and non-Hadir dates (Siswa & Ortu only).
2. `get_schedule`: Daily schedule with cycle and holiday/ineffective day status (Guru, Siswa, Ortu, Waka, BK).
3. `get_my_journal_status`: Journal completion and late alerts for teaching schedule (Guru only).
4. `get_leave_requests`: Scoped leave requests with status and wrapped reasons (Siswa, Ortu, Wali Kelas, BK, Waka).
5. `get_exams`: Published exams, materials, and question banks (scoped like API).
6. `get_class_attendance_summary`: Monthly class attendance recap and Alpa student names (Waka & Tim Kesiswaan/Wali Kelas).
7. `get_unfilled_journals_today`: School-wide unfilled journals for today (Waka only).
8. `get_system_info`: Active cycle, schedule lock status, current time, user role/class.

### Endpoint
```bash
curl -s -X POST http://localhost/api/v1/chat/message \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "message": "Kapan jadwal KBM semester genap dimulai?",
    "history": [
      {"role": "user", "parts": [{"text": "Halo"}]},
      {"role": "model", "parts": [{"text": "Halo! Ada yang bisa saya bantu terkait jadwal sekolah?"}]}
    ]
  }'
```

Response `200 OK`:
```json
{
  "data": {
    "reply": "Berdasarkan kalender akademik sekolah, KBM semester genap dimulai pada..."
  }
}
```

