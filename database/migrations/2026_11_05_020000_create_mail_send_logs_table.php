<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mail_send_logs')) {
            Schema::create('mail_send_logs', function (Blueprint $table) {
                $table->id();
                $table->string('mail_type', 50);
                $table->string('recipient', 150);
                $table->string('status', 20);
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (Schema::hasTable('system_configurations') && ! Schema::hasColumn('system_configurations', 'mail_verify_peer')) {
            Schema::table('system_configurations', function (Blueprint $table) {
                $table->boolean('mail_verify_peer')->default(true)->after('mail_from_name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_send_logs');

        if (Schema::hasTable('system_configurations') && Schema::hasColumn('system_configurations', 'mail_verify_peer')) {
            Schema::table('system_configurations', function (Blueprint $table) {
                $table->dropColumn('mail_verify_peer');
            });
        }
    }
};
