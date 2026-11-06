<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('decided_by_user_id')
                ->nullable()
                ->after('verified_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('decided_by_role', 30)->nullable()->after('decided_by_user_id');
            $table->timestamp('decided_at')->nullable()->after('decided_by_role');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by_user_id');
            $table->dropColumn(['decided_by_role', 'decided_at']);
        });
    }
};
