<?php

namespace Database\Seeders;

use App\Models\Price;
use App\PriceType;
use Illuminate\Database\Seeder;

class PriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            [PriceType::Client, '5000.00'],
            [PriceType::Agent, '7000.00'],
        ] as [$priceType, $pricePerLiter]) {
            $price = Price::query()
                ->where('price_type', $priceType)
                ->whereDate('start_date', '2020-01-01')
                ->first();

            if ($price === null) {
                Price::query()->create([
                    'price_type' => $priceType,
                    'start_date' => '2020-01-01',
                    'end_date' => null,
                    'price_per_liter' => $pricePerLiter,
                ]);

                continue;
            }

            $price->update(['end_date' => null, 'price_per_liter' => $pricePerLiter]);
        }
    }
}
