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
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->boolean('is_journal_time_restricted')->default(true)->after('is_schedule_locked');
            $table->enum('journal_fill_rule', ['during_and_after', 'during_schedule_only', 'anytime'])
                ->default('during_and_after')
                ->after('is_journal_time_restricted');
            $table->unsignedSmallInteger('journal_early_buffer_minutes')->default(0)->after('journal_fill_rule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'is_journal_time_restricted',
                'journal_fill_rule',
                'journal_early_buffer_minutes',
            ]);
        });
    }
};
