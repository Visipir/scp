<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old constraint that restricted 1 class per room per slot
        // and replace it with a class-centric unique constraint: 1 room per class per slot
        DB::statement('DROP INDEX IF EXISTS room_schedule_unique_slot');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS room_schedule_class_slot_unique ON room_schedules (class_id, day_of_week, period, cycle_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS room_schedule_room_slot_index ON room_schedules (room_id, day_of_week, period, cycle_type)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS room_schedule_class_slot_unique');
        DB::statement('DROP INDEX IF EXISTS room_schedule_room_slot_index');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS room_schedule_unique_slot ON room_schedules (room_id, day_of_week, period, cycle_type)');
    }
};
