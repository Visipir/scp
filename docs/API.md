# SCP API (v1)

Base URL: `/api/v1` (e.g. `http://localhost/api/v1`). The legacy `/api/login`,
`/api/me`, `/api/logout` endpoints remain available but are deprecated for the
iOS client. The full contract lives in [`openapi.yaml`](./openapi.yaml).

## Authentication

Stateless Bearer tokens via Laravel Sanctum. Tokens expire after **43200 minutes** (30 days).

1. `POST /api/v1/auth/login` with `{username, password, device_name?}`.
2. Use the returned `data.token` as `Authorization: Bearer <token>`.
3. `POST /api/v1/auth/logout` revokes the current token.

Login is rate limited to **5 attempts per minute per username + IP** (`429` on excess).

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

## Roles

`role_id`: `1` Waka Kurikulum, `2` Guru, `3` Tim Kesiswaan (Wali Kelas / BK),
`4` Siswa/Ortu. A role 3 account with `class_id` is scoped to that homeroom
class; a role 3 account without `class_id` is Guru BK and sees every class.
