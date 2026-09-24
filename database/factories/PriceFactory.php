<?php

namespace Database\Factories;

use App\Models\Price;
use App\PriceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Price>
 */
class PriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price_type' => PriceType::Client,
            'start_date' => '2020-01-01',
            'end_date' => null,
            'price_per_liter' => '5000.00',
        ];
    }

    public function agent(): static
    {
        return $this->state(fn (): array => [
            'price_type' => PriceType::Agent,
            'price_per_liter' => '7000.00',
        ]);
    }
}
