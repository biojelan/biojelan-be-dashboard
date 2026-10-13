<?php

use App\Models\Factory;
use App\Models\User;
use Database\Seeders\RoleSeeder;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated kilang users can visit the dashboard', function () {
    $this->seed(RoleSeeder::class);
    $factory = Factory::factory()->create();
    $user = User::factory()->admin()->create();
    $user->admin()->create(['factory_id' => $factory->factory_id]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
