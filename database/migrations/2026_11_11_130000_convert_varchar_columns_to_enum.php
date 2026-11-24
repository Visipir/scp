<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. attendances.status
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Terlambat'])
                ->default('Hadir')
                ->change();
        });

        // 2. system_configurations.active_cycle
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->enum('active_cycle', ['Putaran 1', 'Putaran 2'])
                ->default('Putaran 1')
                ->change();
        });

        // 3. schedules.day_of_week & cycle_type
        Schema::table('schedules', function (Blueprint $table) {
            $table->enum('day_of_week', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])
                ->change();
            $table->enum('cycle_type', ['Putaran 1', 'Putaran 2'])
                ->change();
        });

        // 4. leave_requests.status_approval
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('status_approval', ['Pending', 'Approved', 'Rejected'])
                ->default('Pending')
                ->change();
        });

        // 5. schedule_swaps.cycle & status
        Schema::table('schedule_swaps', function (Blueprint $table) {
            $table->enum('cycle', ['Putaran 1', 'Putaran 2'])
                ->change();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'expired'])
                ->default('pending')
                ->change();
        });

        // 6. users.gender
        Schema::table('users', function (Blueprint $table) {
            $table->enum('gender', ['L', 'P'])
                ->nullable()
                ->change();
        });

        // 7. exams.category & status
        Schema::table('exams', function (Blueprint $table) {
            $table->enum('category', ['materi', 'bank_soal', 'ujian'])
                ->default('materi')
                ->change();
            $table->enum('status', ['published', 'draft'])
                ->default('published')
                ->change();
        });

        // 8. leave_request_decisions.status & role
        if (Schema::hasTable('leave_request_decisions')) {
            Schema::table('leave_request_decisions', function (Blueprint $table) {
                $table->enum('status', ['Pending', 'Approved', 'Rejected'])
                    ->change();
                $table->enum('role', ['Waka', 'Wali', 'BK', 'Wali Kelas', 'Guru BK'])
                    ->change();
            });
        }

        // 9. schedule_swap_logs.event & actor_role
        if (Schema::hasTable('schedule_swap_logs')) {
            Schema::table('schedule_swap_logs', function (Blueprint $table) {
                $table->enum('event', ['requested', 'approved', 'rejected', 'cancelled', 'expired', 'conflict_blocked'])
                    ->change();
                $table->enum('actor_role', ['guru', 'waka', 'system'])
                    ->default('guru')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status', 255)->default('Hadir')->change();
        });

        Schema::table('system_configurations', function (Blueprint $table) {
            $table->string('active_cycle', 255)->default('Putaran 1')->change();
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->string('day_of_week', 255)->change();
            $table->string('cycle_type', 255)->change();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('status_approval', 255)->default('Pending')->change();
        });

        Schema::table('schedule_swaps', function (Blueprint $table) {
            $table->string('cycle', 255)->change();
            $table->string('status', 255)->default('pending')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 255)->nullable()->change();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->string('category', 255)->default('materi')->change();
            $table->string('status', 255)->default('published')->change();
        });

        if (Schema::hasTable('leave_request_decisions')) {
            Schema::table('leave_request_decisions', function (Blueprint $table) {
                $table->string('status', 30)->change();
                $table->string('role', 30)->change();
            });
        }

        if (Schema::hasTable('schedule_swap_logs')) {
            Schema::table('schedule_swap_logs', function (Blueprint $table) {
                $table->string('event', 255)->change();
                $table->string('actor_role', 255)->default('guru')->change();
            });
        }
    }
};
