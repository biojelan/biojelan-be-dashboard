<?php

namespace Database\Factories;

use App\Models\Factory as FactoryModel;
use App\Models\FinanceEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinanceEntry> */
class FinanceEntryFactory extends Factory
{
    public function definition(): array
    {
        return ['factory_id' => FactoryModel::factory(), 'type' => 'EXPENSE', 'date' => today(), 'amount' => '10000.00', 'volume_liter' => null, 'description' => 'Biaya operasional contoh'];
    }
}
