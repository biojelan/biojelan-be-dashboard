<?php

namespace Database\Factories;

use App\ClientTransactionStatus;
use App\Models\Price;
use App\Models\TransactionAgent;
use App\Models\User;
use App\PriceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionAgent>
 */
class TransactionAgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price_id' => Price::factory()->state(['price_type' => PriceType::Agent]),
            'agent_id' => User::factory()->agen(),
            'driver_id' => User::factory()->driver(),
            'total_price' => '14000.00',
            'volume_liter' => '2.000',
            'status' => ClientTransactionStatus::Pending,
            'transaction_note' => null,
        ];
    }
}
