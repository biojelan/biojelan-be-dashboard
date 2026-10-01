<?php

use App\Models\Pickup;
use App\Models\TransactionAgent;
use App\Models\User;
use App\PickupStatus;
use Database\Seeders\RoleSeeder;

test('drivers update the status of their own pickup using its public id', function () {
    $this->seed(RoleSeeder::class);
    $driver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create();
    $transaction = TransactionAgent::factory()->create([
        'driver_id' => $driver->id,
        'agent_id' => $agent->id,
    ]);
    $pickup = Pickup::factory()->create(['transaction_agent_id' => $transaction->id]);
    $token = $driver->createToken('api')->plainTextToken;

    $this->withToken($token)->patchJson('/api/driver/pickup/status', [
        'pickup_id' => 'pkp-001',
        'status' => PickupStatus::Arrived->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.pickup_id', 'pkp-001')
        ->assertJsonPath('data.status', PickupStatus::Arrived->value)
        ->assertJsonPath('message', 'Success update pickup status!');

    expect($pickup->fresh()->status)->toBe(PickupStatus::Arrived);
});

test('drivers cannot update another drivers pickup status', function () {
    $this->seed(RoleSeeder::class);
    $driver = User::factory()->driver()->create();
    $otherDriver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create();
    $transaction = TransactionAgent::factory()->create([
        'driver_id' => $driver->id,
        'agent_id' => $agent->id,
    ]);
    Pickup::factory()->create(['transaction_agent_id' => $transaction->id]);
    $token = $otherDriver->createToken('api')->plainTextToken;

    $this->withToken($token)->patchJson('/api/driver/pickup/status', [
        'pickup_id' => 'pkp-001',
        'status' => PickupStatus::Otw->value,
    ])->assertNotFound()->assertJsonPath('message', 'Failed update pickup status! Pickup not found.');
});

test('agents and drivers get only their latest active pickup status', function () {
    $this->seed(RoleSeeder::class);
    $driver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create();
    $completedTransaction = TransactionAgent::factory()->create([
        'driver_id' => $driver->id,
        'agent_id' => $agent->id,
    ]);
    Pickup::factory()->create([
        'transaction_agent_id' => $completedTransaction->id,
        'status' => PickupStatus::Completed,
    ]);
    $activeTransaction = TransactionAgent::factory()->create([
        'driver_id' => $driver->id,
        'agent_id' => $agent->id,
    ]);
    $activePickup = Pickup::factory()->create([
        'transaction_agent_id' => $activeTransaction->id,
        'status' => PickupStatus::Otw,
    ]);
    $driverToken = $driver->createToken('api')->plainTextToken;
    $agentToken = $agent->createToken('api')->plainTextToken;

    $this->withToken($driverToken)->getJson('/api/driver/pickup/status')
        ->assertOk()
        ->assertJsonPath('data.pickup_id', sprintf('pkp-%03d', $activePickup->pickup_id))
        ->assertJsonPath('data.status', PickupStatus::Otw->value);

    $this->app->make('auth')->forgetGuards();

    $this->withToken($agentToken)->getJson('/api/agent/pickup/status')
        ->assertOk()
        ->assertJsonPath('data.pickup_id', sprintf('pkp-%03d', $activePickup->pickup_id))
        ->assertJsonPath('message', 'Success get pickup status!');
});

test('only drivers can update pickup status', function () {
    $this->seed(RoleSeeder::class);
    $agent = User::factory()->agen()->create();
    $token = $agent->createToken('api')->plainTextToken;

    $this->withToken($token)->patchJson('/api/driver/pickup/status', [
        'pickup_id' => 'pkp-001',
        'status' => PickupStatus::Otw->value,
    ])->assertForbidden()->assertJsonPath('message', 'Failed access resource! User unauthorized.');
});
