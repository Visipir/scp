CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "room_schedules"(
  "id" integer primary key autoincrement not null,
  "room_id" integer not null,
  "class_id" integer not null,
  "teacher_id" integer,
  "subject" varchar,
  "day_of_week" varchar check("day_of_week" in('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')) not null,
  "period" integer not null,
  "start_time" time not null,
  "end_time" time not null,
  "cycle_type" varchar check("cycle_type" in('Putaran 1', 'Putaran 2')) not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("room_id") references "rooms"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("teacher_id") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "room_schedule_unique_slot" on "room_schedules"(
  "room_id",
  "day_of_week",
  "period",
  "cycle_type"
);
CREATE TABLE IF NOT EXISTS "academic_calendars"(
  "id" integer primary key autoincrement not null,
  "title" varchar not null,
  "start_date" date not null,
  "end_date" date not null,
  "category" varchar check("category" in('libur_nasional', 'libur_bersama', 'agenda_sekolah')) not null,
  "is_kbm_off" tinyint(1) not null default '1',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "role_name" VARCHAR(255) NOT NULL,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "classes"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "class_name" VARCHAR(255) NOT NULL,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "rooms"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "room_name" VARCHAR(255) NOT NULL,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "role_id" integer not null,
  "parent_id" integer,
  "class_id" integer,
  "username" VARCHAR(255) NOT NULL,
  "password" VARCHAR(255) NOT NULL,
  "fullname" VARCHAR(255) NOT NULL,
  "remember_token" VARCHAR(255),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("role_id") references "roles"("id") on delete cascade,
  foreign key("parent_id") references "users"("id") on delete set null,
  foreign key("class_id") references "classes"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "username" VARCHAR(255) NOT NULL,
  "token" VARCHAR(255) NOT NULL,
  "created_at" datetime,
  primary key("username")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" VARCHAR(255) NOT NULL,
  "user_id" integer,
  "ip_address" VARCHAR(255),
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "cache"(
  "key" VARCHAR(255) NOT NULL,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" VARCHAR(255) NOT NULL,
  "owner" VARCHAR(255) NOT NULL,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "queue" VARCHAR(255) NOT NULL,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" VARCHAR(255) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "uuid" VARCHAR(255) NOT NULL,
  "connection" VARCHAR(255) NOT NULL,
  "queue" VARCHAR(255) NOT NULL,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS "system_configurations"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "active_cycle" VARCHAR(255) not null default 'Putaran 1',
  "is_schedule_locked" tinyint(1) not null default '0',
  "updated_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "gemini_api_key" text,
  "gemini_model" varchar,
  "gemini_schedule_model" varchar,
  "gemini_chat_model" varchar,
  foreign key("updated_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "schedules"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "teacher_id" integer not null,
  "class_id" integer not null,
  "room_id" integer not null,
  "subject" VARCHAR(255) NOT NULL,
  "day_of_week" VARCHAR(255) not null,
  "start_time" time not null,
  "end_time" time not null,
  "cycle_type" VARCHAR(255) not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("teacher_id") references "users"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("room_id") references "rooms"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "journals"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "schedule_id" integer not null,
  "date" date not null,
  "topic_description" text not null,
  "photo_path" VARCHAR(255),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("schedule_id") references "schedules"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "leave_requests"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "student_id" integer not null,
  "applicant_id" integer not null,
  "verified_by" integer,
  "start_date" date not null,
  "end_date" date not null,
  "reason" text not null,
  "document_path" VARCHAR(255),
  "status_approval" VARCHAR(255) not null default 'Pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "users"("id") on delete cascade,
  foreign key("applicant_id") references "users"("id") on delete cascade,
  foreign key("verified_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "attendances"(
  "id" INTEGER PRIMARY KEY AUTOINCREMENT not null,
  "journal_id" integer not null,
  "student_id" integer not null,
  "status" VARCHAR(255) not null default 'Hadir',
  "date" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  "notes" varchar,
  foreign key("journal_id") references "journals"("id") on delete cascade,
  foreign key("student_id") references "users"("id") on delete cascade
);
CREATE TABLE ineffective_days(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  date DATE NOT NULL,
  description TEXT,
  created_by INTEGER,
  created_at DATETIME,
  updated_at DATETIME
);
CREATE TABLE IF NOT EXISTS "exams"(
  "id" integer primary key autoincrement not null,
  "teacher_id" integer not null,
  "class_id" integer not null,
  "subject" varchar not null,
  "title" varchar not null,
  "exam_date" date not null,
  "description" text,
  "material_file_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("teacher_id") references "users"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete cascade
);

INSERT INTO migrations VALUES(1,'2026_09_07_104644_create_room_schedules_table',1);
INSERT INTO migrations VALUES(2,'2026_09_08_004409_create_academic_calendars_table',1);
INSERT INTO migrations VALUES(3,'2026_09_08_071747_add_notes_to_attendances_table',1);
INSERT INTO migrations VALUES(4,'2024_01_01_000001_create_system_configurations_table',1);
INSERT INTO migrations VALUES(5,'2024_01_01_000002_create_schedules_table',1);
INSERT INTO migrations VALUES(6,'2024_01_01_000003_create_journals_table',1);
INSERT INTO migrations VALUES(7,'2024_01_01_000004_create_leave_requests_table',1);
INSERT INTO migrations VALUES(8,'2024_01_01_000005_create_attendances_table',1);
INSERT INTO migrations VALUES(9,'2026_09_08_075913_create_exams_table',2);
INSERT INTO migrations VALUES(10,'2026_09_08_083545_add_gemini_columns_to_system_configurations_table',3);
