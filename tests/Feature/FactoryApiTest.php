<?php

use App\Models\Factory;
use App\Models\User;
use Database\Seeders\FactorySeeder;
use Database\Seeders\RoleSeeder;

test('agents and drivers can get the factory location', function (string $state): void {
    $this->seed([RoleSeeder::class, FactorySeeder::class]);
    $user = User::factory()->{$state}()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/user/factory')
        ->assertOk()
        ->assertJsonPath('data.factory_name', 'Kilang Biojelan')
        ->assertJsonPath('data.address', 'Jl. Contoh No. 1, Bandar Lampung')
        ->assertJsonPath('data.latitude', -5.3971)
        ->assertJsonPath('data.longitude', 105.2668)
        ->assertJsonPath('message', 'Success get kilang!');
})->with(['agent' => 'agen', 'driver' => 'driver']);

test('clients cannot get the factory location', function (): void {
    $this->seed(RoleSeeder::class);
    $client = User::factory()->client()->create();
    $token = $client->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/user/factory')
        ->assertForbidden()
        ->assertJsonPath('message', 'Failed access resource! User unauthorized.');
});

test('returns 401 when no token is provided', function (): void {
    $this->getJson('/api/user/factory')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Failed get kilang! User unauthorized.');
});

test('returns 404 when no factory is available', function (): void {
    $this->seed(RoleSeeder::class);
    $agent = User::factory()->agen()->create();
    $token = $agent->createToken('api')->plainTextToken;

    expect(Factory::query()->exists())->toBeFalse();

    $this->withToken($token)->getJson('/api/user/factory')
        ->assertNotFound()
        ->assertJsonPath('message', 'Failed get kilang! Factory not found.');
});
