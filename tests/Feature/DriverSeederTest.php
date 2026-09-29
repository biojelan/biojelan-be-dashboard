<?php

use App\Models\Driver;
use App\Models\User;
use Database\Seeders\DriverSeeder;
use Illuminate\Support\Facades\Hash;

test('driver seeding creates the driver account and vehicle profile', function () {
    $this->seed(DriverSeeder::class);

    $user = User::query()->where('email', 'driver@mail.com')->firstOrFail();
    $driver = Driver::query()->findOrFail($user->id);

    expect($user->role_id)->toBe(User::DRIVER_ROLE_ID);
    expect(Hash::check('driver123', $user->password))->toBeTrue();
    expect($driver->plate_number)->toBe('BE 1234 BJ');
    expect($driver->type_vehicle)->toBe('pickup');
});

test('repeated driver seeding preserves an existing vehicle profile', function () {
    $this->seed(DriverSeeder::class);
    $user = User::query()->where('email', 'driver@mail.com')->firstOrFail();
    $user->driver->update(['plate_number' => 'BE 9999 XX']);

    $this->seed(DriverSeeder::class);

    $this->assertDatabaseCount('drivers', 1);
    $this->assertDatabaseHas('drivers', ['driver_id' => $user->id, 'plate_number' => 'BE 9999 XX']);
});
