<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_request_decisions')) {
            Schema::table('leave_request_decisions', function (Blueprint $table) {
                $table->enum('role', ['Waka', 'Wali', 'BK', 'Wali Kelas', 'Guru BK', 'Superadmin'])
                    ->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leave_request_decisions')) {
            Schema::table('leave_request_decisions', function (Blueprint $table) {
                $table->enum('role', ['Waka', 'Wali', 'BK', 'Wali Kelas', 'Guru BK'])
                    ->change();
            });
        }
    }
};
