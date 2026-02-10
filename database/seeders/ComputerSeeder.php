<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComputerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labs = ['Lab 1', 'Lab 2'];

        foreach ($labs as $lab) {
            for ($i = 1; $i <= 20; $i++) {
                \App\Models\Computer::create([
                    'lab_name' => $lab,
                    'pc_number' => 'PC-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'status' => 'available',
                    'asset_tag' => "AU-{$lab}-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }
}
