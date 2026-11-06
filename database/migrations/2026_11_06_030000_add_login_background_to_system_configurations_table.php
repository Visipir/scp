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
            if (!Schema::hasColumn('system_configurations', 'login_background_path')) {
                $table->string('login_background_path')->nullable()->after('is_schedule_locked');
            }
            if (!Schema::hasColumn('system_configurations', 'login_background_updated_by')) {
                $table->unsignedBigInteger('login_background_updated_by')->nullable()->after('login_background_path');
            }
            if (!Schema::hasColumn('system_configurations', 'login_background_updated_at')) {
                $table->timestamp('login_background_updated_at')->nullable()->after('login_background_updated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('system_configurations', 'login_background_path')) {
                $cols[] = 'login_background_path';
            }
            if (Schema::hasColumn('system_configurations', 'login_background_updated_by')) {
                $cols[] = 'login_background_updated_by';
            }
            if (Schema::hasColumn('system_configurations', 'login_background_updated_at')) {
                $cols[] = 'login_background_updated_at';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
