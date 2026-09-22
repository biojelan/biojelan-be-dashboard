<?php

namespace Database\Factories;

use App\Models\Agen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agen>
 */
class AgenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agen_id' => User::factory()->agen(),
            'address' => fake()->address(),
            'latitude' => (string) fake()->latitude(),
            'longitude' => (string) fake()->longitude(),
            'bank_name' => 'BCA',
            'account_number' => fake()->numerify('##########'),
            'open_at' => '08:00:00',
            'close_at' => '17:00:00',
            'open_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_open' => false,
            'stock_liter' => 0,
        ];
    }
}
