<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            'Lab RPL 1',
            'Lab RPL 2',
            'Lab Jaringan & Komputer',
            'Lab Multimedia',
            'Ruang Teori 101',
            'Ruang Teori 102',
            'Ruang Teori 201',
            'Ruang Teori 202',
            'Bengkel Komputer',
        ];

        foreach ($rooms as $roomName) {
            Room::firstOrCreate(['room_name' => $roomName]);
        }
    }
}
