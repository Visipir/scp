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
            $table->text('gemini_api_key')->nullable();
            $table->string('gemini_model')->nullable();
            $table->string('gemini_schedule_model')->nullable();
            $table->string('gemini_chat_model')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'gemini_api_key',
                'gemini_model',
                'gemini_schedule_model',
                'gemini_chat_model',
            ]);
        });
    }
};
