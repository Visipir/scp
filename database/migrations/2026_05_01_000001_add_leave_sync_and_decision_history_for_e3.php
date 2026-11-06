<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('attendances', 'leave_request_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->foreignId('leave_request_id')
                    ->nullable()
                    ->after('student_id')
                    ->constrained('leave_requests')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('leave_request_decisions')) {
            Schema::create('leave_request_decisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('role', 30);
                $table->string('status', 30);
                $table->timestamp('decided_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_decisions');

        if (Schema::hasColumn('attendances', 'leave_request_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropConstrainedForeignId('leave_request_id');
            });
        }
    }
};
