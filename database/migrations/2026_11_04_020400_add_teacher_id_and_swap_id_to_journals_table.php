<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('schedule_id')->constrained('users')->nullOnDelete();
            $table->foreignId('swap_id')->nullable()->after('teacher_id')->constrained('schedule_swaps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['swap_id']);
            $table->dropColumn(['teacher_id', 'swap_id']);
        });
    }
};
