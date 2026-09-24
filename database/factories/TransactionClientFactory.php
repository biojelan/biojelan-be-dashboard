<?php

namespace Database\Factories;

use App\ClientTransactionStatus;
use App\Models\Price;
use App\Models\TransactionClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionClient>
 */
class TransactionClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price_id' => Price::factory(),
            'client_id' => User::factory(),
            'agent_id' => User::factory(),
            'total_price' => '10000.00',
            'volume_liter' => '2.000',
            'status' => ClientTransactionStatus::Pending,
            'transaction_note' => null,
        ];
    }
}
