<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            'X PPLG 1',
            'X PPLG 2',
            'XI RPL 1',
            'XI RPL 2',
            'XII RPL 1',
            'XII RPL 2',
            'XI TKJ 1',
            'XII TKJ 1',
        ];

        foreach ($classes as $className) {
            SchoolClass::firstOrCreate(
                ['class_name' => $className],
                ['academic_year' => '2026/2027']
            );
        }

        // Update any existing classes without academic_year
        SchoolClass::whereNull('academic_year')->orWhere('academic_year', '')->update(['academic_year' => '2026/2027']);
    }
}
