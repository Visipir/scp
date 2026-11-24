<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                $table->enum('category', ['Izin', 'Sakit', 'Dispensasi', 'Lainnya'])
                    ->default('Izin')
                    ->after('end_date')
                    ->nullable();
            } else {
                $table->string('category', 50)
                    ->default('Izin')
                    ->after('end_date')
                    ->nullable();
            }
        });

        // Backfill existing records: categorize based on reason
        $sicknessKeywords = [
            'sakit', 'demam', 'flu', 'batuk', 'pilek', 'pusing', 'mual', 'muntah',
            'dokter', 'puskesmas', 'opname', 'rawat', 'medis', 'klinik', 'hospital',
            'isoman', 'tipes', 'tifus', 'cacar', 'diare', 'cedera', 'kesehatan',
            'istirahat medis', 'kondisi badan', 'badan panas', 'panas dingin', 'bedrest', 'bed rest',
        ];

        $dispensationKeywords = [
            'lomba', 'lks', 'osis', 'dispensasi', 'tugas sekolah', 'paskibra', 'pramuka', 'olimpiade',
        ];

        $leaves = DB::table('leave_requests')->select('id', 'reason')->get();
        foreach ($leaves as $leave) {
            $reason = strtolower($leave->reason ?? '');
            $category = 'Izin';

            foreach ($sicknessKeywords as $kw) {
                if (str_contains($reason, $kw)) {
                    $category = 'Sakit';
                    break;
                }
            }

            if ($category === 'Izin') {
                foreach ($dispensationKeywords as $kw) {
                    if (str_contains($reason, $kw)) {
                        $category = 'Dispensasi';
                        break;
                    }
                }
            }

            DB::table('leave_requests')->where('id', $leave->id)->update(['category' => $category]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
