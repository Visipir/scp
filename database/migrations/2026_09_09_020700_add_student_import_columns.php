<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add grade_level and major to classes
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'grade_level')) {
                $table->string('grade_level', 10)->nullable()->after('class_name');
            }
            if (!Schema::hasColumn('classes', 'major')) {
                $table->string('major', 50)->nullable()->after('grade_level');
            }
        });

        // Add gender and attendance_number to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 1)->nullable()->after('fullname');
            }
            if (!Schema::hasColumn('users', 'attendance_number')) {
                $table->integer('attendance_number')->nullable()->after('class_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['grade_level', 'major']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'attendance_number']);
        });
    }
};
