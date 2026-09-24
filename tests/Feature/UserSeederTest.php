<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

test('seeding creates an active account with a hashed password for every other role', function () {
    $this->seed(UserSeeder::class);

    $this->assertDatabaseCount('users', 6);

    foreach ([1 => 'superadmin', 2 => 'admin', 3 => 'manajer_finance', 4 => 'manajer_operasional', 5 => 'driver', 6 => 'agent'] as $id => $role) {
        $user = User::query()->where('email', $role.'@mail.com')->firstOrFail();
        expect($user->role_id)->toBe($id);
        expect($user->is_active)->toBeTrue();
        expect(Hash::check($role.'123', $user->password))->toBeTrue();
    }
});

test('repeated user seeding preserves existing passwords and account settings', function () {
    $this->seed(UserSeeder::class);
    $user = User::query()->where('email', 'admin@mail.com')->firstOrFail();
    $user->forceFill(['password' => 'changed-password', 'is_active' => false])->save();

    $this->seed(UserSeeder::class);

    $this->assertDatabaseCount('users', 6);
    expect(Hash::check('changed-password', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->is_active)->toBeFalse();
});

test('user seeding renames the legacy default agen account without duplicating its profile owner', function () {
    $this->seed(RoleSeeder::class);
    $legacyAgent = User::factory()->create([
        'email' => 'agen@mail.com',
        'password' => 'agen123',
        'role_id' => 6,
    ]);

    $this->seed(UserSeeder::class);

    $agent = User::query()->where('email', 'agent@mail.com')->firstOrFail();
    expect($agent->id)->toBe($legacyAgent->id);
    expect(Hash::check('agent123', $agent->password))->toBeTrue();
    $this->assertDatabaseMissing('users', ['email' => 'agen@mail.com']);
});
