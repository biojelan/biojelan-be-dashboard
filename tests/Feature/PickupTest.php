<?php

use App\Models\Pickup;
use App\Models\TransactionAgent;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;

test('a pickup belongs to one transaction and stores its schedule', function () {
    $this->seed(RoleSeeder::class);
    $transaction = TransactionAgent::factory()->create();

    $pickup = Pickup::factory()->create([
        'transaction_agent_id' => $transaction->id,
        'date' => '2026-10-01',
        'time' => '14:30:00',
    ]);

    expect($pickup->transactionAgent->is($transaction))->toBeTrue();
    expect($transaction->pickup->is($pickup))->toBeTrue();
    expect($pickup->driver_id)->toBe($transaction->driver_id);
    expect($pickup->agent_id)->toBe($transaction->agent_id);
    expect($pickup->date->toDateString())->toBe('2026-10-01');
    expect($pickup->time)->toBe('14:30:00');

    $this->assertDatabaseHas('pickups', [
        'pickup_id' => $pickup->pickup_id,
        'transaction_agent_id' => $transaction->id,
        'time' => '14:30:00',
    ]);
});

test('a transaction can only have one pickup', function () {
    $this->seed(RoleSeeder::class);
    $transaction = TransactionAgent::factory()->create();
    Pickup::factory()->create(['transaction_agent_id' => $transaction->id]);

    expect(fn () => Pickup::factory()->create(['transaction_agent_id' => $transaction->id]))
        ->toThrow(QueryException::class);
});
