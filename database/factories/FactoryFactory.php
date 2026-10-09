<?php

namespace Database\Factories;

use App\Models\Factory as FactoryModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FactoryModel>
 */
class FactoryFactory extends Factory
{
    protected $model = FactoryModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'factory_name' => fake()->company().' Factory',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}
