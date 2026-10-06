<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lab;
use App\Models\Computer;

class ComputerSeeder extends Seeder
{
    public function run(): void
    {
        $labs = [
            'CL1' => 'Room 204',
            'CL2' => 'Room 205',
            'CL3' => 'Room 406',
            'CL4' => 'Room 407',
        ];

        foreach ($labs as $name => $location) {

            $lab = Lab::create([
                'name' => $name,
                'location' => $location,
                'capacity' => 20,
            ]);

            for ($i = 1; $i <= 20; $i++) {
                Computer::create([
                    'lab_id' => $lab->id,
                    'pc_number' => 'PC-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'status' => 'available',
                    'asset_tag' => 'AU-' . $name . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }
}
