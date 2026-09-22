<?php

use App\Models\Agen;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

pest()->use(LazilyRefreshDatabase::class);

test('users retain their existing fields and receive profile defaults', function () {
    $user = User::factory()->create(['phone' => '081234567890'])->refresh();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'email' => $user->email,
        'phone' => '081234567890',
        'role_id' => null,
        'is_verified' => false,
        'is_active' => true,
    ]);
    expect($user->is_verified)->toBeFalse();
    expect($user->is_active)->toBeTrue();
});

test('an agen belongs to one agen user and serializes open days as an array', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->agen()->create();
    $attributes = Agen::factory()->make([
        'agen_id' => $user->id,
        'account_number' => '0012345678',
        'open_days' => ['monday', 'friday'],
        'stock_liter' => 12.5,
    ])->toArray();

    $agen = $user->agen()->create($attributes)->refresh();

    expect($agen->user->is($user))->toBeTrue();
    expect($user->fresh()->agen->is($agen))->toBeTrue();
    expect($agen->toArray()['open_days'])->toBe(['monday', 'friday']);
    expect($agen->stock_liter)->toBe(12.5);
    expect($agen->is_open)->toBeFalse();
    $this->assertDatabaseHas('agen', [
        'agen_id' => $user->id,
        'account_number' => '0012345678',
        'open_at' => '08:00:00',
        'close_at' => '17:00:00',
    ]);
});

test('users without the agen role cannot own an agen profile', function (?int $roleId) {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['role_id' => $roleId]);

    expect(fn () => Agen::factory()->for($user, 'user')->create())
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('agen', 0);
})->with(['admin' => 2, 'no role' => null]);

test('one user cannot have multiple agen profiles', function () {
    $this->seed(RoleSeeder::class);
    $agen = Agen::factory()->create();

    expect(fn () => Agen::factory()->for($agen->user, 'user')->create())
        ->toThrow(UniqueConstraintViolationException::class);

    $this->assertDatabaseCount('agen', 1);
});

test('an agen cannot be reassigned to a user with a different role', function () {
    $this->seed(RoleSeeder::class);
    $agen = Agen::factory()->create();
    $originalUserId = $agen->agen_id;
    $admin = User::factory()->create(['role_id' => 2]);
    $agen->user()->associate($admin);

    expect(fn () => $agen->save())->toThrow(ValidationException::class);

    $this->assertDatabaseHas('agen', ['agen_id' => $originalUserId]);
    $this->assertDatabaseMissing('agen', ['agen_id' => $admin->id]);
});

test('an agen user cannot change role while the agen profile exists', function () {
    $this->seed(RoleSeeder::class);
    $agen = Agen::factory()->create();
    $user = $agen->user;
    $user->role_id = 2;

    expect(fn () => $user->save())->toThrow(ValidationException::class);

    $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => 6]);
});

test('a user can change role after removing the agen profile', function () {
    $this->seed(RoleSeeder::class);
    $agen = Agen::factory()->create();
    $user = $agen->user;
    $agen->delete();
    $user->role_id = 2;

    $user->save();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => 2]);
});

test('deleting an agen user also removes the agen profile', function () {
    $this->seed(RoleSeeder::class);
    $agen = Agen::factory()->create();

    $agen->user->delete();

    $this->assertModelMissing($agen);
});
