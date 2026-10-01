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
        Schema::table('exams', function (Blueprint $table) {
            $table->string('category')->default('materi')->after('subject'); // 'materi', 'bank_soal', 'ujian'
            $table->string('external_link')->nullable()->after('material_file_path');
            $table->string('file_name')->nullable()->after('material_file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
            $table->time('start_time')->nullable()->after('exam_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->string('status')->default('published')->after('end_time'); // 'published', 'draft'
            
            $table->index(['category', 'exam_date']);
            $table->index(['class_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex(['category', 'exam_date']);
            $table->dropIndex(['class_id', 'category']);
            $table->dropColumn([
                'category',
                'external_link',
                'file_name',
                'file_size',
                'start_time',
                'end_time',
                'status',
            ]);
        });
    }
};
