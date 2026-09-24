<?php

use App\ClientTransactionStatus;
use App\Models\Price;
use App\Models\TransactionClient;
use App\Models\User;
use Database\Seeders\PriceSeeder;
use Database\Seeders\RoleSeeder;

test('agents create a transaction for a registered client with the active client price', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $agent = User::factory()->create(['role_id' => User::AGEN_ROLE_ID]);
    $client = User::factory()->create(['role_id' => User::CLIENT_ROLE_ID]);
    $token = $agent->createToken('api')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/agent/transaction', [
        'client_email' => $client->email,
        'volume_liter' => '2',
        'transaction_note' => 'Jemput siang hari.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.transaction_id', 'trx-clients-001')
        ->assertJsonPath('data.agen_id', $agent->id)
        ->assertJsonPath('data.client_id', $client->id)
        ->assertJsonPath('data.client_name', $client->name)
        ->assertJsonPath('data.price', 5000)
        ->assertJsonPath('data.total_price', 10000)
        ->assertJsonPath('data.status', ClientTransactionStatus::Pending->value)
        ->assertJsonPath('message', 'Success create transaction!');

    $this->assertDatabaseHas('transactions_client', [
        'transaction_id' => 'trx-clients-001',
        'price_id' => Price::query()->firstOrFail()->price_id,
        'client_id' => $client->id,
        'agent_id' => $agent->id,
        'volume_liter' => 2,
        'total_price' => 10000,
        'status' => ClientTransactionStatus::Pending->value,
        'transaction_note' => 'Jemput siang hari.',
    ]);
});

test('agents create a guest transaction only with the reserved guest email', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $agent = User::factory()->create(['role_id' => User::AGEN_ROLE_ID]);
    $token = $agent->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/agent/transaction', [
        'client_email' => 'guest.client@biojelan.id',
        'volume_liter' => '1.5',
    ])->assertCreated()->assertJsonPath('data.client_id', null);

    $this->assertDatabaseHas('transactions_client', ['client_id' => null, 'volume_liter' => 1.5]);

    $this->withToken($token)->postJson('/api/agent/transaction', [
        'client_email' => 'unregistered@example.com',
        'volume_liter' => '1.5',
    ])->assertUnprocessable()->assertJsonPath('message', 'Failed create transaction! Client not registered.');
});

test('agents must submit exactly one client identifier and a positive volume', function (array $payload, string $message) {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $agent = User::factory()->create(['role_id' => User::AGEN_ROLE_ID]);
    $token = $agent->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/agent/transaction', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', $message);
})->with([
    'both email and phone' => [[
        'client_email' => 'guest.client@biojelan.id',
        'client_phone' => '081234567890',
        'volume_liter' => '1',
    ], 'Failed create transaction! Choose either client email or phone.'],
    'zero volume' => [[
        'client_email' => 'guest.client@biojelan.id',
        'volume_liter' => '0',
    ], 'Failed create transaction! Invalid volume liter.'],
]);

test('only agents can create client transactions', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $client = User::factory()->create(['role_id' => User::CLIENT_ROLE_ID]);
    $token = $client->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/agent/transaction', [
        'client_email' => 'guest.client@biojelan.id',
        'volume_liter' => '1',
    ])->assertForbidden()->assertJsonPath('message', 'Failed access resource! User unauthorized.');
});

test('agents check clients separately by email and phone', function () {
    $this->seed(RoleSeeder::class);
    $agent = User::factory()->create(['role_id' => User::AGEN_ROLE_ID]);
    $client = User::factory()->create([
        'role_id' => User::CLIENT_ROLE_ID,
        'phone' => '081234567890',
    ]);
    $token = $agent->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/agent/check-clients-email', ['client_email' => $client->email])
        ->assertOk()
        ->assertJsonPath('data.is_exist', true)
        ->assertJsonPath('data.client_id', $client->id)
        ->assertJsonPath('data.client_phone', '081234567890');

    $this->withToken($token)->postJson('/api/agent/check-clients-phone', ['client_phone' => 'missing'])
        ->assertOk()
        ->assertJsonPath('data.is_exist', false)
        ->assertJsonPath('data.client_id', null);
});

test('agents can view only their own client transactions', function () {
    $this->seed(RoleSeeder::class);
    $agent = User::factory()->create(['role_id' => User::AGEN_ROLE_ID]);
    $otherAgent = User::factory()->create(['role_id' => User::AGEN_ROLE_ID]);
    $client = User::factory()->create(['role_id' => User::CLIENT_ROLE_ID]);
    $ownTransaction = TransactionClient::factory()->create(['agent_id' => $agent->id, 'client_id' => $client->id]);
    TransactionClient::factory()->create(['agent_id' => $otherAgent->id, 'client_id' => $client->id]);
    $token = $agent->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/agent/clients/transactions')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.transaction_id', $ownTransaction->transaction_id);
});

test('clients can view their transactions and filter by status', function () {
    $this->seed(RoleSeeder::class);
    $client = User::factory()->create(['role_id' => User::CLIENT_ROLE_ID]);
    $agent = User::factory()->create(['role_id' => User::AGEN_ROLE_ID, 'name' => 'Agent Name']);
    $pending = TransactionClient::factory()->create([
        'client_id' => $client->id,
        'agent_id' => $agent->id,
        'status' => ClientTransactionStatus::Pending,
    ]);
    TransactionClient::factory()->create([
        'client_id' => $client->id,
        'agent_id' => $agent->id,
        'status' => ClientTransactionStatus::Accepted,
    ]);
    $token = $client->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/client/transactions?status=PENDING')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.transaction_id', $pending->transaction_id)
        ->assertJsonPath('data.0.agen_name', 'Agent Name')
        ->assertJsonPath('message', 'Success get transaction status pending!');
});

test('clients update their pending transaction response', function (string $action, ClientTransactionStatus $status, string $message) {
    $this->seed(RoleSeeder::class);
    $client = User::factory()->create(['role_id' => User::CLIENT_ROLE_ID]);
    $transaction = TransactionClient::factory()->create(['client_id' => $client->id]);
    $token = $client->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/client/transaction/'.$transaction->transaction_id.'/'.$action)
        ->assertOk()
        ->assertJsonPath('data.status', $status->value)
        ->assertJsonPath('message', $message);

    expect($transaction->fresh()->status)->toBe($status);
})->with([
    'accept' => ['accept', ClientTransactionStatus::Accepted, 'Success accept transaction!'],
    'reject' => ['reject', ClientTransactionStatus::Rejected, 'Success reject transaction!'],
]);

test('agents request cancellation and clients decide the request', function (string $action, ClientTransactionStatus $status, string $message) {
    $this->seed(RoleSeeder::class);
    $agent = User::factory()->create(['role_id' => User::AGEN_ROLE_ID]);
    $client = User::factory()->create(['role_id' => User::CLIENT_ROLE_ID]);
    $transaction = TransactionClient::factory()->create([
        'agent_id' => $agent->id,
        'client_id' => $client->id,
        'status' => ClientTransactionStatus::Accepted,
    ]);
    $agentToken = $agent->createToken('api')->plainTextToken;
    $clientToken = $client->createToken('api')->plainTextToken;

    $this->withToken($agentToken)->postJson('/api/agent/transaction/'.$transaction->transaction_id.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', ClientTransactionStatus::CancelRequested->value);

    $this->app->make('auth')->forgetGuards();

    $this->withToken($clientToken)->postJson('/api/client/transaction/'.$transaction->transaction_id.'/'.$action)
        ->assertOk()
        ->assertJsonPath('data.status', $status->value)
        ->assertJsonPath('message', $message);

    expect($transaction->fresh()->status)->toBe($status);
})->with([
    'accept cancellation' => ['cancel-accept', ClientTransactionStatus::Cancelled, 'Success accept cancel transaction!'],
    'reject cancellation' => ['cancel-reject', ClientTransactionStatus::Accepted, 'Success reject cancel transaction!'],
]);

test('clients cannot access another clients transaction', function () {
    $this->seed(RoleSeeder::class);
    $owner = User::factory()->create(['role_id' => User::CLIENT_ROLE_ID]);
    $otherClient = User::factory()->create(['role_id' => User::CLIENT_ROLE_ID]);
    $transaction = TransactionClient::factory()->create(['client_id' => $owner->id]);
    $token = $otherClient->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/client/transaction/'.$transaction->transaction_id.'/accept')->assertNotFound();
    expect($transaction->fresh()->status)->toBe(ClientTransactionStatus::Pending);
});

test('guest transactions cannot be accepted by an authenticated client', function () {
    $this->seed([RoleSeeder::class, PriceSeeder::class]);
    $agent = User::factory()->create(['role_id' => User::AGEN_ROLE_ID]);
    $client = User::factory()->create(['role_id' => User::CLIENT_ROLE_ID]);
    $agentToken = $agent->createToken('api')->plainTextToken;
    $clientToken = $client->createToken('api')->plainTextToken;

    $this->withToken($agentToken)->postJson('/api/agent/transaction', [
        'client_email' => 'guest.client@biojelan.id',
        'volume_liter' => '1',
    ])->assertCreated();

    $transaction = TransactionClient::query()->firstOrFail();
    $this->app->make('auth')->forgetGuards();

    $this->withToken($clientToken)->postJson('/api/client/transaction/'.$transaction->transaction_id.'/accept')->assertNotFound();
});

test('user profile updates reject a phone number that already belongs to another user', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['phone' => '081234567890']);
    $otherUser = User::factory()->create();
    $token = $otherUser->createToken('api')->plainTextToken;

    $this->withToken($token)->patchJson('/api/user', ['phone' => '081234567890'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Failed update user! Phone already exists.');
});
