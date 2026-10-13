<?php

use App\Actions\Kilang\FactoryInventory;
use App\ClientTransactionStatus;
use App\Models\Agen;
use App\Models\AgentApplication;
use App\Models\Driver;
use App\Models\Factory;
use App\Models\FinanceEntry;
use App\Models\Pickup;
use App\Models\Price;
use App\Models\TransactionAgent;
use App\Models\TransactionClient;
use App\Models\User;
use App\PickupStatus;
use App\PriceType;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia as Assert;

function dashboardActor(int $role = 2, ?Factory $factory = null): User
{
    $factory ??= Factory::factory()->create();
    $user = User::factory()->create(['role_id' => $role]);
    $user->admin()->create(['factory_id' => $factory->factory_id]);

    return $user;
}
function dashboardUserPayload(int $role = 7): array
{
    return ['name' => 'New User', 'email' => 'new@mail.com', 'phone' => '08123456789', 'password' => 'password123', 'password_confirmation' => 'password123', 'role_id' => $role, 'is_active' => true,
        'profile' => ['address' => 'Bandar Lampung', 'latitude' => -5.3971, 'longitude' => 105.2668, 'bank_name' => 'MANDIRI', 'account_number' => '1234567890', 'open_at' => '08:00', 'close_at' => '17:00', 'open_days' => ['senin'], 'plate_number' => 'BE 1111 BJ', 'type_vehicle' => 'Pickup']];
}

test('dashboard permissions follow the four kilang roles', function (int $role, bool $users, bool $operations, bool $finance): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor($role);
    $this->actingAs($actor)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('kilang/dashboard')->where('kilang.permissions.users', $users)->where('kilang.permissions.operations', $operations)->where('kilang.permissions.finance', $finance));
    $this->get(route('kilang.users.index'))->assertStatus($users ? 200 : 403);
    $this->get(route('kilang.operations.index'))->assertStatus($operations ? 200 : 403);
    $this->get(route('kilang.finance.index'))->assertStatus($finance ? 200 : 403);
})->with(['superadmin' => [1, true, true, true], 'admin' => [2, true, true, true], 'operations' => [4, false, true, false], 'finance' => [3, false, false, true]]);

test('non kilang and inactive accounts cannot enter the dashboard', function (int $role, bool $active): void {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['role_id' => $role, 'is_active' => $active]);
    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
})->with([[5, true], [6, true], [7, true], [2, false]]);

test('operations responses contain stock and never transaction amounts', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor(4);
    $transaction = TransactionAgent::factory()->create();
    Pickup::factory()->create(['transaction_agent_id' => $transaction->id, 'factory_id' => $actor->admin->factory_id]);
    $this->actingAs($actor)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('stats.finance', null));
    $this->get(route('kilang.operations.index'))->assertInertia(fn (Assert $page) => $page->has('inventory')->missing('report')->missing('pickups.data.0.transaction_agent.total_price'));
    $this->post(route('kilang.finance.price'), ['price_type' => 'AGENT', 'price_per_liter' => 9000])->assertForbidden();
    $this->get(route('kilang.finance.pdf'))->assertForbidden();
});

test('only superadmin can create admin accounts and no second superadmin is allowed', function (): void {
    $this->seed(RoleSeeder::class);
    $superadmin = dashboardActor(1);
    $admin = dashboardActor(2, $superadmin->admin->factory);
    $this->actingAs($admin)->post(route('kilang.users.store'), dashboardUserPayload(2))->assertSessionHasErrors('role_id');
    $this->actingAs($superadmin)->post(route('kilang.users.store'), dashboardUserPayload(2))->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('users', ['email' => 'new@mail.com', 'role_id' => 2]);
    $this->post(route('kilang.users.store'), [...dashboardUserPayload(1), 'email' => 'extra@mail.com'])->assertSessionHasErrors('role_id');
    expect(fn () => User::factory()->create(['role_id' => 1]))->toThrow(QueryException::class);
});

test('admin can create client agent driver and manager accounts with their profiles', function (int $role): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor();
    $this->actingAs($actor)->post(route('kilang.users.store'), dashboardUserPayload($role))->assertSessionHasNoErrors()->assertRedirect();
    $user = User::query()->where('email', 'new@mail.com')->firstOrFail();
    expect($user->role_id)->toBe($role);
    if ($role === 6) {
        expect($user->agen->address)->toBe('Bandar Lampung')->and($user->agen->stock_liter)->toBe(0.0);
    }
    if ($role === 5) {
        expect($user->driver->plate_number)->toBe('BE 1111 BJ');
    }
    if (in_array($role, [3, 4], true)) {
        expect($user->admin->factory_id)->toBe($actor->admin->factory_id);
    }
})->with([7, 6, 5, 3, 4]);

test('client application is reviewed once and approval creates the agent profile', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor();
    $application = AgentApplication::factory()->create();
    $token = $application->user->createToken('api')->plainTextToken;
    $this->withToken($token)->postJson('/api/user/agent-request', ['profile' => $application->profile])->assertUnprocessable();
    $this->app->make('auth')->forgetGuards();
    $this->actingAs($actor)->post(route('kilang.applications.review', $application), ['decision' => 'APPROVED'])->assertSessionHasNoErrors();
    expect($application->fresh()->status)->toBe('APPROVED')->and($application->user->fresh()->role_id)->toBe(6)->and($application->user->fresh()->agen->stock_liter)->toBe(0.0);
    $this->post(route('kilang.applications.review', $application), ['decision' => 'REJECTED', 'review_note' => 'Ditolak'])->assertSessionHasErrors('decision');
});

test('client can submit an agent application and rejection allows resubmission', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor();
    $client = User::factory()->client()->create();
    $token = $client->createToken('api')->plainTextToken;
    $profile = dashboardUserPayload(6)['profile'];
    $this->withToken($token)->postJson('/api/user/agent-request', ['profile' => $profile])->assertCreated()->assertJsonPath('data.status', 'PENDING');
    $application = AgentApplication::query()->firstOrFail();
    $this->app->make('auth')->forgetGuards();
    $this->actingAs($actor)->post(route('kilang.applications.review', $application), ['decision' => 'REJECTED', 'review_note' => 'Lengkapi alamat'])->assertSessionHasNoErrors();
    expect($client->fresh()->role_id)->toBe(7);
    $this->app->make('auth')->forgetGuards();
    $this->withToken($token)->postJson('/api/user/agent-request', ['profile' => $profile])->assertCreated();
    $this->assertDatabaseCount('agent_applications', 1);
});

test('account history prevents deletion and deactivation revokes tokens', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor();
    $transaction = TransactionAgent::factory()->create();
    $agent = $transaction->agent;
    Agen::factory()->create(['agen_id' => $agent->id]);
    $agent->createToken('api');
    $this->actingAs($actor)->delete(route('kilang.users.destroy', $agent))->assertSessionHasErrors('user');
    $payload = [...dashboardUserPayload(6), 'email' => $agent->email, 'phone' => null, 'is_active' => false];
    $this->patch(route('kilang.users.update', $agent), $payload)->assertSessionHasNoErrors();
    expect($agent->fresh()->is_active)->toBeFalse()->and($agent->tokens()->count())->toBe(0);
    $client = User::factory()->client()->create();
    $this->delete(route('kilang.users.destroy', $client))->assertSessionHasNoErrors();
    $this->assertModelMissing($client);
});

test('pickup route reserves stock once with one transaction for each ordered stop', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor(4);
    $driver = Driver::factory()->create();
    $first = Agen::factory()->create(['stock_liter' => 80]);
    $second = Agen::factory()->create(['stock_liter' => 60]);
    Price::factory()->create(['price_type' => PriceType::Agent, 'start_date' => today()->subDay(), 'end_date' => null, 'price_per_liter' => '7000.00']);
    $this->actingAs($actor)->post(route('kilang.pickups.store'), ['driver_id' => $driver->driver_id, 'date' => today()->addDay()->toDateString(), 'time' => '10:00', 'stops' => [['agent_id' => $first->agen_id, 'volume_liter' => 50], ['agent_id' => $second->agen_id, 'volume_liter' => 60]]])->assertSessionHasNoErrors();
    expect($first->fresh()->stock_liter)->toBe(30.0)->and($second->fresh()->stock_liter)->toBe(0.0);
    $pickups = Pickup::query()->orderBy('stop_order')->get();
    expect($pickups)->toHaveCount(2)->and($pickups[0]->route_key)->toBe($pickups[1]->route_key)->and($pickups[0]->agent_id)->toBe($first->agen_id)->and($pickups[1]->agent_id)->toBe($second->agen_id);
    $this->assertDatabaseCount('transactions_agent', 2);
    $this->app->make('auth')->forgetGuards();
    $token = $driver->user->createToken('api')->plainTextToken;
    $this->withToken($token)->getJson('/api/driver/pickup/status')->assertOk()->assertJsonPath('data.agent.agent_id', $first->agen_id);
});

test('insufficient pickup stock rolls back every route stop', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor(4);
    $driver = Driver::factory()->create();
    $first = Agen::factory()->create(['stock_liter' => 80]);
    $second = Agen::factory()->create(['stock_liter' => 20]);
    Price::factory()->create(['price_type' => PriceType::Agent, 'start_date' => today()->subDay(), 'end_date' => null]);
    $this->actingAs($actor)->post(route('kilang.pickups.store'), ['driver_id' => $driver->driver_id, 'date' => today()->addDay()->toDateString(), 'time' => '10:00', 'stops' => [['agent_id' => $first->agen_id, 'volume_liter' => 50], ['agent_id' => $second->agen_id, 'volume_liter' => 50]]])->assertSessionHasErrors('stops.1.volume_liter');
    expect($first->fresh()->stock_liter)->toBe(80.0);
    $this->assertDatabaseCount('transactions_agent', 0);
    $this->assertDatabaseCount('pickups', 0);
});

test('factory reports count completed pickups once and never duplicate client agent costs', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor(3);
    $factory = $actor->admin->factory;
    $transaction = TransactionAgent::factory()->create(['total_price' => '140000.00', 'volume_liter' => '20.000', 'status' => ClientTransactionStatus::Accepted]);
    Pickup::factory()->create(['factory_id' => $factory->factory_id, 'transaction_agent_id' => $transaction->id, 'status' => PickupStatus::Completed, 'completed_at' => now()]);
    TransactionClient::factory()->create(['total_price' => '100000.00', 'status' => ClientTransactionStatus::Accepted]);
    FinanceEntry::factory()->create(['factory_id' => $factory->factory_id, 'type' => 'SALE', 'amount' => '200000.00', 'volume_liter' => '10.000']);
    FinanceEntry::factory()->create(['factory_id' => $factory->factory_id, 'type' => 'EXPENSE', 'amount' => '10000.00']);
    $this->actingAs($actor)->get(route('kilang.finance.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('report.income', 200000)->where('report.purchase_cost', 140000)->where('report.total_expense', 150000)->where('report.profit', 50000)->where('inventory.stock_liter', 10));
    $response = $this->get(route('kilang.finance.pdf'));
    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-1.4')->toContain('startxref')->toContain('LAPORAN KEUANGAN BIOJELAN');
});

test('finance sales cannot exceed received factory stock', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor(3);
    $this->actingAs($actor)->post(route('kilang.finance.store'), ['type' => 'SALE', 'date' => today()->toDateString(), 'amount' => 50000, 'volume_liter' => 10, 'description' => 'Penjualan'])->assertSessionHasErrors('volume_liter');
    $this->assertDatabaseCount('finance_entries', 0);
});

test('same day price changes are immutable and public api returns the newest client price', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor(3);
    $old = Price::factory()->create(['price_type' => PriceType::Client, 'start_date' => today(), 'end_date' => null, 'price_per_liter' => '5000.00']);
    $this->actingAs($actor)->post(route('kilang.finance.price'), ['price_type' => 'CLIENT', 'price_per_liter' => 5500])->assertSessionHasNoErrors();
    $this->post(route('kilang.finance.price'), ['price_type' => 'CLIENT', 'price_per_liter' => 6000])->assertSessionHasNoErrors();
    expect($old->fresh()->price_per_liter)->toBe('5000.00');
    $this->getJson('/api/price')->assertOk()->assertJsonPath('data.price_per_liter', 6000);
});

test('admin cannot modify privileged accounts or deactivate their own account', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor(2);
    $superadmin = dashboardActor(1, $actor->admin->factory);
    $this->actingAs($actor)->patch(route('kilang.users.update', $superadmin), dashboardUserPayload(1))->assertForbidden();
    $this->delete(route('kilang.users.destroy', $superadmin))->assertForbidden();
});

test('pickup completion is counted once and cannot be cancelled afterwards', function (): void {
    $this->seed(RoleSeeder::class);
    $factory = Factory::factory()->create();
    $transaction = TransactionAgent::factory()->create(['status' => ClientTransactionStatus::Accepted, 'volume_liter' => '50.000']);
    $pickup = Pickup::factory()->create(['transaction_agent_id' => $transaction->id, 'factory_id' => $factory->factory_id]);
    $token = $transaction->driver->createToken('api')->plainTextToken;
    $id = sprintf('pkp-%03d', $pickup->pickup_id);
    $this->withToken($token)->patchJson('/api/driver/pickup/status', ['pickup_id' => $id, 'status' => 'COMPLETED'])->assertOk();
    $completedAt = $pickup->fresh()->completed_at->toISOString();
    $this->patchJson('/api/driver/pickup/status', ['pickup_id' => $id, 'status' => 'COMPLETED'])->assertOk();
    expect($pickup->fresh()->completed_at->toISOString())->toBe($completedAt);
    expect(app(FactoryInventory::class)->totals($factory)['received_liter'])->toBe(50.0);
    $this->patchJson('/api/driver/pickup/status', ['pickup_id' => $id, 'status' => 'CANCELLED'])->assertUnprocessable()->assertJsonPath('message', 'Failed update pickup status! Pickup is already final.');
    $this->postJson('/api/driver/transaction/'.$transaction->transaction_id.'/cancel')->assertUnprocessable();
});

test('pending transactions cannot be completed and rejection cancels their scheduled pickup', function (): void {
    $this->seed(RoleSeeder::class);
    $profile = Agen::factory()->create(['stock_liter' => 30]);
    $transaction = TransactionAgent::factory()->create(['agent_id' => $profile->agen_id, 'status' => ClientTransactionStatus::Pending, 'volume_liter' => '50.000']);
    $pickup = Pickup::factory()->create(['transaction_agent_id' => $transaction->id]);
    $driverToken = $transaction->driver->createToken('api')->plainTextToken;
    $this->withToken($driverToken)->patchJson('/api/driver/pickup/status', ['pickup_id' => sprintf('pkp-%03d', $pickup->pickup_id), 'status' => 'COMPLETED'])->assertUnprocessable()->assertJsonPath('message', 'Failed update pickup status! Transaction must be accepted first.');
    $this->app->make('auth')->forgetGuards();
    $agentToken = $profile->user->createToken('api')->plainTextToken;
    $this->withToken($agentToken)->postJson('/api/agent/transaction/'.$transaction->transaction_id.'/reject')->assertOk();
    expect($pickup->fresh()->status)->toBe(PickupStatus::Cancelled)->and($profile->fresh()->stock_liter)->toBe(80.0);
});

test('superadmin cannot delete their account through existing api or settings', function (): void {
    $this->seed(RoleSeeder::class);
    $superadmin = dashboardActor(1);
    $this->actingAs($superadmin)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('password');
    $token = $superadmin->createToken('api')->plainTextToken;
    $this->withToken($token)->deleteJson('/api/user')->assertForbidden()->assertJsonPath('message', 'Failed delete user! Superadmin cannot be deleted.');
    $this->assertModelExists($superadmin);
});

test('legacy users without a role can be assigned the client role', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor();
    $legacy = User::factory()->create(['role_id' => null]);
    $this->actingAs($actor)->patch(route('kilang.users.update', $legacy), dashboardUserPayload(7))->assertSessionHasNoErrors();
    expect($legacy->fresh()->role_id)->toBe(7);
});

test('factory finance totals exclude entries and pickups of another factory', function (): void {
    $this->seed(RoleSeeder::class);
    $actor = dashboardActor(3);
    $other = Factory::factory()->create();
    $transaction = TransactionAgent::factory()->create(['status' => ClientTransactionStatus::Accepted]);
    Pickup::factory()->create(['transaction_agent_id' => $transaction->id, 'factory_id' => $other->factory_id, 'status' => PickupStatus::Completed, 'completed_at' => now()]);
    FinanceEntry::factory()->create(['factory_id' => $other->factory_id, 'type' => 'SALE', 'amount' => '200000.00', 'volume_liter' => '1.000']);
    $this->actingAs($actor)->get(route('kilang.finance.index'))->assertInertia(fn (Assert $page) => $page->where('report.income', 0)->where('report.purchase_cost', 0)->where('inventory.received_liter', 0));
});
