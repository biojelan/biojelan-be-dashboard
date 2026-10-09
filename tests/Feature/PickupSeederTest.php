<?php

use App\ClientTransactionStatus;
use App\Models\Pickup;
use App\Models\User;
use App\PickupStatus;
use Database\Seeders\PickupSeeder;

test('pickup seeder creates an example for every pickup status', function (): void {
    $this->seed(PickupSeeder::class);

    $agent = User::query()->where('email', 'agent@mail.com')->firstOrFail();
    $driver = User::query()->where('email', 'driver@mail.com')->firstOrFail();

    foreach (PickupStatus::cases() as $status) {
        $pickup = Pickup::query()
            ->with('transactionAgent')
            ->where('status', $status->value)
            ->firstOrFail();

        expect($pickup->agent_id)->toBe($agent->id)
            ->and($pickup->driver_id)->toBe($driver->id)
            ->and($pickup->transactionAgent->status)->toBe(
                $status === PickupStatus::Cancelled
                    ? ClientTransactionStatus::Cancelled
                    : ClientTransactionStatus::Accepted,
            );
    }
});

test('repeated pickup seeding does not create duplicate examples', function (): void {
    $this->seed(PickupSeeder::class);

    $this->seed(PickupSeeder::class);

    $this->assertDatabaseCount('pickups', 5);
    $this->assertDatabaseCount('transactions_agent', 5);
});
