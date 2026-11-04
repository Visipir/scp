<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_swaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requester_schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('target_schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->string('cycle');
            $table->date('swap_date');
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled, expired
            $table->text('note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['swap_date', 'status']);
            $table->index(['requester_id', 'status']);
            $table->index(['target_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_swaps');
    }
};
