<?php

use App\ClientTransactionStatus;
use App\Models\Agen;
use App\Models\Price;
use App\Models\TransactionAgent;
use App\Models\User;
use Database\Seeders\PriceSeeder;
use Database\Seeders\RoleSeeder;

test('drivers create a transaction with the active agent price and reserve agent stock', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $driver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create(['name' => 'Agent Name']);
    Agen::factory()->create(['agen_id' => $agent->id, 'stock_liter' => 5]);
    $token = $driver->createToken('api')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/driver/transaction', [
        'agent_email' => $agent->email,
        'volume_liter' => '2',
        'transaction_note' => 'The oil will be picked up this afternoon.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.transaction_id', 'trx-agent-001')
        ->assertJsonPath('data.driver_id', $driver->id)
        ->assertJsonPath('data.agent_id', $agent->id)
        ->assertJsonPath('data.agent_name', 'Agent Name')
        ->assertJsonPath('data.volume_liter', 2)
        ->assertJsonPath('data.price', 7000)
        ->assertJsonPath('data.total_price', 14000)
        ->assertJsonPath('data.status', ClientTransactionStatus::Pending->value)
        ->assertJsonPath('data.transaction_note', 'The oil will be picked up this afternoon.')
        ->assertJsonPath('message', 'Success create transaction!');

    $this->assertDatabaseHas('transactions_agent', [
        'transaction_id' => 'trx-agent-001',
        'price_id' => Price::query()->where('price_type', 'AGENT')->value('price_id'),
        'driver_id' => $driver->id,
        'agent_id' => $agent->id,
        'volume_liter' => 2,
        'total_price' => 14000,
        'status' => ClientTransactionStatus::Pending->value,
    ]);
    expect($agent->agen->fresh()->stock_liter)->toBe(3.0);
});

test('drivers can identify an agent by phone number', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $driver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create(['phone' => '081234567890']);
    Agen::factory()->create(['agen_id' => $agent->id, 'stock_liter' => 3]);
    $token = $driver->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/driver/transaction', [
        'agent_phone' => '081234567890',
        'volume_liter' => '1.5',
    ])
        ->assertCreated()
        ->assertJsonPath('data.agent_id', $agent->id);
});

test('drivers cannot create a transaction when agent stock is insufficient', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $driver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create();
    Agen::factory()->create(['agen_id' => $agent->id, 'stock_liter' => 1]);
    $token = $driver->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/driver/transaction', [
        'agent_email' => $agent->email,
        'volume_liter' => '2',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Failed create transaction! Agent stock is insufficient.');

    $this->assertDatabaseCount('transactions_agent', 0);
    expect($agent->agen->fresh()->stock_liter)->toBe(1.0);
});

test('drivers must submit exactly one agent identifier and a positive volume', function (array $payload, string $message) {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $driver = User::factory()->driver()->create();
    $token = $driver->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/driver/transaction', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', $message);
})->with([
    'both email and phone' => [[
        'agent_email' => 'agent@example.com',
        'agent_phone' => '081234567890',
        'volume_liter' => '1',
    ], 'Failed create transaction! Choose either agent email or phone.'],
    'zero volume' => [[
        'agent_email' => 'agent@example.com',
        'volume_liter' => '0',
    ], 'Failed create transaction! Invalid volume liter.'],
]);

test('only drivers can create agent transactions', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $agent = User::factory()->agen()->create();
    $token = $agent->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/driver/transaction', [
        'agent_email' => 'agent@example.com',
        'volume_liter' => '1',
    ])->assertForbidden()->assertJsonPath('message', 'Failed access resource! User unauthorized.');
});

test('drivers can view only their own transactions', function () {
    $this->seed(RoleSeeder::class);
    $driver = User::factory()->driver()->create();
    $otherDriver = User::factory()->driver()->create();
    $transaction = TransactionAgent::factory()->create(['driver_id' => $driver->id]);
    TransactionAgent::factory()->create(['driver_id' => $otherDriver->id]);
    $token = $driver->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/driver/transactions')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.transaction_id', $transaction->transaction_id);
});

test('agents can view their own driver transactions and filter by status', function () {
    $this->seed(RoleSeeder::class);
    $agent = User::factory()->agen()->create();
    $otherAgent = User::factory()->agen()->create();
    $pending = TransactionAgent::factory()->create([
        'agent_id' => $agent->id,
        'status' => ClientTransactionStatus::Pending,
    ]);
    TransactionAgent::factory()->create([
        'agent_id' => $agent->id,
        'status' => ClientTransactionStatus::Accepted,
    ]);
    TransactionAgent::factory()->create(['agent_id' => $otherAgent->id]);
    $token = $agent->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/agent/transactions?status=PENDING')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.transaction_id', $pending->transaction_id)
        ->assertJsonPath('message', 'Success get pending transactions!');

    $this->withToken($token)->getJson('/api/agent/driver/transactions')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('agents can accept their own pending transaction without restoring reserved stock', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $driver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create();
    Agen::factory()->create(['agen_id' => $agent->id, 'stock_liter' => 5]);
    $driverToken = $driver->createToken('api')->plainTextToken;
    $agentToken = $agent->createToken('api')->plainTextToken;

    $this->withToken($driverToken)->postJson('/api/driver/transaction', [
        'agent_email' => $agent->email,
        'volume_liter' => '2',
    ])->assertCreated();

    $transaction = TransactionAgent::query()->firstOrFail();
    $this->app->make('auth')->forgetGuards();

    $this->withToken($agentToken)->postJson('/api/agent/transaction/'.$transaction->transaction_id.'/accept')
        ->assertOk()
        ->assertJsonPath('data.status', ClientTransactionStatus::Accepted->value)
        ->assertJsonPath('message', 'Success accept transaction!');

    expect($transaction->fresh()->status)->toBe(ClientTransactionStatus::Accepted);
    expect($agent->agen->fresh()->stock_liter)->toBe(3.0);
});

test('agents update their own transaction status and restore stock when it is rejected', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $driver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create();
    Agen::factory()->create(['agen_id' => $agent->id, 'stock_liter' => 5]);
    $driverToken = $driver->createToken('api')->plainTextToken;
    $agentToken = $agent->createToken('api')->plainTextToken;

    $this->withToken($driverToken)->postJson('/api/driver/transaction', [
        'agent_email' => $agent->email,
        'volume_liter' => '2',
    ])->assertCreated();

    $transaction = TransactionAgent::query()->firstOrFail();
    $this->app->make('auth')->forgetGuards();

    $this->withToken($agentToken)->postJson('/api/agent/transaction/'.$transaction->transaction_id.'/reject')
        ->assertOk()
        ->assertJsonPath('data.status', ClientTransactionStatus::Rejected->value)
        ->assertJsonPath('message', 'Success reject transaction!');

    expect($transaction->fresh()->status)->toBe(ClientTransactionStatus::Rejected);
    expect($agent->agen->fresh()->stock_liter)->toBe(5.0);
});

test('drivers request cancellation and agents decide the request', function (string $action, ClientTransactionStatus $status, float $stock, string $message) {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $driver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create();
    Agen::factory()->create(['agen_id' => $agent->id, 'stock_liter' => 5]);
    $driverToken = $driver->createToken('api')->plainTextToken;
    $agentToken = $agent->createToken('api')->plainTextToken;

    $this->withToken($driverToken)->postJson('/api/driver/transaction', [
        'agent_email' => $agent->email,
        'volume_liter' => '2',
    ])->assertCreated();

    $transaction = TransactionAgent::query()->firstOrFail();
    $this->withToken($driverToken)->postJson('/api/driver/transaction/'.$transaction->transaction_id.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', ClientTransactionStatus::CancelRequested->value);

    $this->app->make('auth')->forgetGuards();

    $this->withToken($agentToken)->postJson('/api/agent/transaction/'.$transaction->transaction_id.'/'.$action)
        ->assertOk()
        ->assertJsonPath('data.status', $status->value)
        ->assertJsonPath('message', $message);

    expect($transaction->fresh()->status)->toBe($status);
    expect($agent->agen->fresh()->stock_liter)->toBe($stock);
})->with([
    'accept cancellation' => ['cancel-accept', ClientTransactionStatus::Cancelled, 5.0, 'Success accept cancel transaction!'],
    'reject cancellation' => ['cancel-reject', ClientTransactionStatus::Accepted, 3.0, 'Success reject cancel transaction!'],
]);

test('agents and drivers cannot access another users transaction', function () {
    $this->seed(RoleSeeder::class);
    $driver = User::factory()->driver()->create();
    $otherDriver = User::factory()->driver()->create();
    $agent = User::factory()->agen()->create();
    $otherAgent = User::factory()->agen()->create();
    $transaction = TransactionAgent::factory()->create([
        'driver_id' => $driver->id,
        'agent_id' => $agent->id,
    ]);
    $otherDriverToken = $otherDriver->createToken('api')->plainTextToken;
    $otherAgentToken = $otherAgent->createToken('api')->plainTextToken;

    $this->withToken($otherDriverToken)->postJson('/api/driver/transaction/'.$transaction->transaction_id.'/cancel')->assertNotFound();
    $this->app->make('auth')->forgetGuards();
    $this->withToken($otherAgentToken)->postJson('/api/agent/transaction/'.$transaction->transaction_id.'/accept')->assertNotFound();
});
