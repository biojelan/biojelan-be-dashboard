<?php

namespace Database\Seeders;

use App\ClientTransactionStatus;
use App\Models\Pickup;
use App\Models\Price;
use App\Models\TransactionAgent;
use App\Models\User;
use App\PickupStatus;
use App\PriceType;
use Illuminate\Database\Seeder;

class PickupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([AgenSeeder::class, DriverSeeder::class, PriceSeeder::class]);

        $agent = User::query()
            ->where('email', 'agent@mail.com')
            ->where('role_id', User::AGEN_ROLE_ID)
            ->firstOrFail();
        $driver = User::query()
            ->where('email', 'driver@mail.com')
            ->where('role_id', User::DRIVER_ROLE_ID)
            ->firstOrFail();
        $price = Price::query()
            ->where('price_type', PriceType::Agent)
            ->whereNull('end_date')
            ->latest('start_date')
            ->firstOrFail();

        foreach ($this->pickupExamples() as $example) {
            $transaction = TransactionAgent::query()->firstOrCreate(
                [
                    'agent_id' => $agent->id,
                    'driver_id' => $driver->id,
                    'transaction_note' => 'Seed pickup: '.$example['status']->value,
                ],
                [
                    'price_id' => $price->price_id,
                    'total_price' => $example['total_price'],
                    'volume_liter' => $example['volume_liter'],
                    'status' => $example['transaction_status'],
                ],
            );

            Pickup::query()->firstOrCreate(
                ['transaction_agent_id' => $transaction->id],
                [
                    'driver_id' => $driver->id,
                    'agent_id' => $agent->id,
                    'date' => $example['date'],
                    'time' => $example['time'],
                    'status' => $example['status'],
                    'destination' => $example['destination'],
                ],
            );
        }
    }

    /**
     * @return list<array{
     *     status: PickupStatus,
     *     transaction_status: ClientTransactionStatus,
     *     date: string,
     *     time: string,
     *     destination: string,
     *     volume_liter: string,
     *     total_price: string
     * }>
     */
    private function pickupExamples(): array
    {
        return [
            [
                'status' => PickupStatus::Assigned,
                'transaction_status' => ClientTransactionStatus::Accepted,
                'date' => '2026-10-11',
                'time' => '08:00:00',
                'destination' => 'Jl. Jenderal Sudirman No. 1, Bandar Lampung',
                'volume_liter' => '20.000',
                'total_price' => '140000.00',
            ],
            [
                'status' => PickupStatus::Arrived,
                'transaction_status' => ClientTransactionStatus::Accepted,
                'date' => '2026-10-10',
                'time' => '09:30:00',
                'destination' => 'Jl. Jenderal Sudirman No. 1, Bandar Lampung',
                'volume_liter' => '25.000',
                'total_price' => '175000.00',
            ],
            [
                'status' => PickupStatus::Completed,
                'transaction_status' => ClientTransactionStatus::Accepted,
                'date' => '2026-10-09',
                'time' => '13:00:00',
                'destination' => 'Jl. Jenderal Sudirman No. 1, Bandar Lampung',
                'volume_liter' => '15.000',
                'total_price' => '105000.00',
            ],
            [
                'status' => PickupStatus::Cancelled,
                'transaction_status' => ClientTransactionStatus::Cancelled,
                'date' => '2026-10-08',
                'time' => '10:00:00',
                'destination' => 'Jl. Jenderal Sudirman No. 1, Bandar Lampung',
                'volume_liter' => '10.000',
                'total_price' => '70000.00',
            ],
            [
                'status' => PickupStatus::Otw,
                'transaction_status' => ClientTransactionStatus::Accepted,
                'date' => '2026-10-10',
                'time' => '11:00:00',
                'destination' => 'Jl. Jenderal Sudirman No. 1, Bandar Lampung',
                'volume_liter' => '30.000',
                'total_price' => '210000.00',
            ],
        ];
    }
}
