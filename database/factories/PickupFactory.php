<?php

namespace Database\Factories;

use App\Models\Pickup;
use App\Models\TransactionAgent;
use App\Models\User;
use App\PickupStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pickup>
 */
class PickupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_agent_id' => TransactionAgent::factory(),
            'driver_id' => User::factory()->driver(),
            'agent_id' => User::factory()->agen(),
            'date' => fake()->dateTimeBetween('now', '+1 week'),
            'time' => fake()->time(),
            'status' => PickupStatus::Assigned,
            'destination' => fake()->address(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Pickup $pickup): void {
            $transaction = TransactionAgent::query()->findOrFail($pickup->transaction_agent_id);

            $pickup->driver_id = $transaction->driver_id;
            $pickup->agent_id = $transaction->agent_id;
        });
    }
}
