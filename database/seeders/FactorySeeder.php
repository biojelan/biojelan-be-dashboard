<?php

namespace Database\Seeders;

use App\Models\Factory;
use Illuminate\Database\Seeder;

class FactorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Factory::query()->firstOrCreate(
            ['factory_name' => 'Kilang Biojelan'],
            [
                'address' => 'Jl. Contoh No. 1, Bandar Lampung',
                'latitude' => -5.3971000,
                'longitude' => 105.2668000,
            ],
        );
    }
}
