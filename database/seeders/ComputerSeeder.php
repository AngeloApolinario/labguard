<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lab;
use App\Models\Computer;

class ComputerSeeder extends Seeder
{
    public function run(): void
    {
        $labNames = ['Lab 1', 'Lab 2', 'Lab 3'];

        foreach ($labNames as $name) {

            $lab = Lab::create([
                'name' => $name,
                'location' => 'Araullo University Main',
                'capacity' => 20,
            ]);

            // 2. Create the 20 PCs for THIS specific lab using its ID
            for ($i = 1; $i <= 20; $i++) {
                Computer::create([
                    'lab_id' => $lab->id, // Linking via Foreign Key
                    'pc_number' => 'PC-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'status' => 'available',
                    'asset_tag' => "AU-" . str_replace(' ', '', $name) . "-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }
}
