<?php

namespace Database\Factories;

use App\Models\AgentApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgentApplication> */
class AgentApplicationFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory()->client(), 'status' => 'PENDING', 'profile' => [
            'address' => fake()->address(), 'latitude' => -5.3971, 'longitude' => 105.2668,
            'bank_name' => 'MANDIRI', 'account_number' => '1234567890', 'open_at' => '08:00', 'close_at' => '17:00', 'open_days' => ['senin', 'selasa'],
        ]];
    }
}
