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
        if (!Schema::hasColumn('users', 'nip')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nip', 30)->nullable()->unique()->after('fullname');
            });
        }

        if (!Schema::hasTable('homeroom_assignments')) {
            Schema::create('homeroom_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('class_id')->unique()->constrained('classes')->cascadeOnDelete();
                $table->string('academic_year', 20)->default('2026/2027');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('counselor_assignments')) {
            Schema::create('counselor_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->string('academic_year', 20)->default('2026/2027');
                $table->timestamps();

                $table->unique(['user_id', 'class_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counselor_assignments');
        Schema::dropIfExists('homeroom_assignments');

        if (Schema::hasColumn('users', 'nip')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('nip');
            });
        }
    }
};
