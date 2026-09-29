<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_id' => User::factory()->driver(),
            'plate_number' => fake()->unique()->bothify('B #### ???'),
            'type_vehicle' => fake()->randomElement(['pickup', 'truck']),
        ];
    }
}
