<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

pest()->use(LazilyRefreshDatabase::class);

test('database seeding creates the seven roles with their assigned ids', function () {
    $this->seed();

    $this->assertDatabaseCount('role', 7);
    $this->assertDatabaseHas('role', ['id' => 1, 'role' => 'superadmin']);
    $this->assertDatabaseHas('role', ['id' => 2, 'role' => 'admin']);
    $this->assertDatabaseHas('role', ['id' => 3, 'role' => 'manajer_finance']);
    $this->assertDatabaseHas('role', ['id' => 4, 'role' => 'manajer_operasional']);
    $this->assertDatabaseHas('role', ['id' => 5, 'role' => 'driver']);
    $this->assertDatabaseHas('role', ['id' => 6, 'role' => 'agent']);
    $this->assertDatabaseHas('role', ['id' => 7, 'role' => 'client']);
});

test('role seeding can be repeated without duplicating records', function () {
    $this->seed(RoleSeeder::class);

    $this->seed(RoleSeeder::class);

    $this->assertDatabaseCount('role', 7);
});
