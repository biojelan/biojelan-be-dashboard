<?php

namespace Database\Seeders;

use App\Models\Factory;
use App\Models\FinanceEntry;
use Illuminate\Database\Seeder;

class FinanceEntrySeeder extends Seeder
{
    public function run(): void
    {
        $this->call(FactorySeeder::class);
        FinanceEntry::query()->firstOrCreate(['factory_id' => Factory::query()->oldest('factory_id')->value('factory_id'), 'description' => 'Contoh biaya operasional (data demo)'], ['type' => 'EXPENSE', 'date' => today(), 'amount' => '10000.00']);
    }
}
