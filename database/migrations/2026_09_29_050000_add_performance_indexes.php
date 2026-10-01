<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_username_unique ON users (username)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_class_id_index ON users (class_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_role_id_index ON users (role_id)');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS classes_class_name_academic_year_unique ON classes (class_name, academic_year)');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS rooms_room_name_unique ON rooms (room_name)');
        DB::statement('CREATE INDEX IF NOT EXISTS schedules_teacher_id_index ON schedules (teacher_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS schedules_class_id_index ON schedules (class_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS schedules_day_cycle_index ON schedules (day_of_week, cycle_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS room_schedules_class_day_cycle_index ON room_schedules (class_id, day_of_week, cycle_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS academic_calendars_dates_index ON academic_calendars (start_date, end_date, is_kbm_off)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_username_unique');
        DB::statement('DROP INDEX IF EXISTS users_class_id_index');
        DB::statement('DROP INDEX IF EXISTS users_role_id_index');
        DB::statement('DROP INDEX IF EXISTS classes_class_name_academic_year_unique');
        DB::statement('DROP INDEX IF EXISTS rooms_room_name_unique');
        DB::statement('DROP INDEX IF EXISTS schedules_teacher_id_index');
        DB::statement('DROP INDEX IF EXISTS schedules_class_id_index');
        DB::statement('DROP INDEX IF EXISTS schedules_day_cycle_index');
        DB::statement('DROP INDEX IF EXISTS room_schedules_class_day_cycle_index');
        DB::statement('DROP INDEX IF EXISTS academic_calendars_dates_index');
    }
};
