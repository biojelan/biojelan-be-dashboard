<?php

use App\Models\Agen;
use App\Models\User;
use Database\Seeders\RoleSeeder;

test('authenticated users can retrieve their complete profile', function () {
    $user = User::factory()->create(['phone' => '081319306262', 'is_verified' => true]);
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/user')
        ->assertOk()->assertExactJson([
            'data' => [
                'user_id' => $user->id,
                'role_id' => null,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '081319306262',
                'is_verified' => true,
                'is_active' => true,
                'agen' => null,
            ],
            'message' => 'Success get user!',
        ]);
});

test('authenticated agen users can retrieve their profile with agen data', function () {
    $this->seed(RoleSeeder::class);
    $agen = Agen::factory()->create(['open_days' => ['senin', 'selasa'], 'stock_liter' => 50]);
    $token = $agen->user->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/user')
        ->assertOk()->assertJsonPath('data.user_id', $agen->agen_id)
        ->assertJsonPath('data.role_id', 6)
        ->assertJsonPath('data.agen.agen_id', $agen->agen_id)
        ->assertJsonPath('data.agen.open_days', ['senin', 'selasa'])
        ->assertJsonPath('data.agen.stock_liter', 50)
        ->assertJsonPath('message', 'Success get user!');
});

test('public agen listing returns active agen users without bank details', function () {
    $this->seed(RoleSeeder::class);
    $visible = Agen::factory()->create(['open_days' => ['senin']]);
    $inactive = Agen::factory()->create(['open_days' => ['minggu']]);
    $inactive->user->forceFill(['is_active' => false])->save();

    $this->getJson('/api/user/agen')
        ->assertOk()->assertJsonPath('message', 'Success get user agen!')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.agen_id', $visible->agen_id)
        ->assertJsonPath('data.0.role_id', 6)
        ->assertJsonPath('data.0.open_days', ['senin'])
        ->assertJsonMissingPath('data.0.bank_name')
        ->assertJsonMissingPath('data.0.account_number');
});

test('users can update permitted profile attributes without changing their password', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;
    $originalPassword = $user->password;

    $this->withToken($token)->patchJson('/api/user', [
        'name' => 'Updated User',
        'email' => 'updated@mail.com',
        'phone' => '081319306263',
        'password' => 'attempted-password',
    ])->assertUnprocessable()->assertJsonPath('message', 'Failed update user! The password field is prohibited.');

    expect($user->fresh()->password)->toBe($originalPassword);
    $this->withToken($token)->patchJson('/api/user', [
        'name' => 'Updated User',
        'email' => 'updated@mail.com',
        'phone' => '081319306263',
    ])->assertOk()->assertJsonPath('data.name', 'Updated User')
        ->assertJsonPath('data.email', 'updated@mail.com')
        ->assertJsonPath('data.phone', '081319306263')
        ->assertJsonPath('message', 'Success update user!');

    expect($user->fresh()->password)->toBe($originalPassword);
});

test('users cannot update their profile with an email used by another account', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->patchJson('/api/user', ['email' => $other->email])
        ->assertUnprocessable()->assertJsonPath('message', 'Failed update user! Email already exists.');

    expect($user->fresh()->email)->not->toBe($other->email);
});

test('agen users can create an agen profile from their own profile endpoint', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->agen()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->patchJson('/api/user', [
        'agen' => [
            'address' => 'Bandarlampung', 'latitude' => 101.111, 'longitude' => 102.222,
            'bank_name' => 'MANDIRI', 'account_number' => '1234567890',
            'open_at' => '08:00', 'close_at' => '20:00',
            'open_days' => ['senin', 'selasa'], 'is_open' => true,
        ],
    ])->assertOk()->assertJsonPath('data.agen.agen_id', $user->id)
        ->assertJsonPath('data.agen.open_days', ['senin', 'selasa'])
        ->assertJsonPath('data.agen.is_open', true);

    $this->assertDatabaseHas('agen', ['agen_id' => $user->id, 'address' => 'Bandarlampung']);
});

test('agen users can partially update an existing agen profile', function () {
    $this->seed(RoleSeeder::class);
    $agen = Agen::factory()->create(['address' => 'Old Address', 'is_open' => false]);
    $token = $agen->user->createToken('api')->plainTextToken;

    $this->withToken($token)->patchJson('/api/user', [
        'agen' => ['address' => 'New Address', 'is_open' => true, 'open_days' => ['minggu']],
    ])->assertOk()->assertJsonPath('data.agen.address', 'New Address')
        ->assertJsonPath('data.agen.is_open', true)
        ->assertJsonPath('data.agen.open_days', ['minggu']);

    $this->assertDatabaseHas('agen', ['agen_id' => $agen->agen_id, 'address' => 'New Address', 'is_open' => true]);
});

test('only agen users can create or update an agen profile', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['role_id' => 7]);
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->patchJson('/api/user', ['agen' => []])
        ->assertUnprocessable()->assertJsonPath('message', 'Failed update user! Agen profile can only be updated by an agen user.');

    $this->assertDatabaseCount('agen', 0);
});

test('agen profile creation requires every non-default profile field', function () {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->agen()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->patchJson('/api/user', ['agen' => ['address' => 'Bandarlampung']])
        ->assertUnprocessable()->assertJsonPath('message', 'Failed update user! The latitude field is required when creating an agen profile.');

    $this->assertDatabaseCount('agen', 0);
});

test('deleting an account revokes all tokens and deletes its agen profile', function () {
    $this->seed(RoleSeeder::class);
    $agen = Agen::factory()->create();
    $user = $agen->user;
    $token = $user->createToken('current')->plainTextToken;
    $user->createToken('other');

    $this->withToken($token)->deleteJson('/api/user')
        ->assertOk()->assertExactJson(['data' => [], 'message' => 'Success delete user!']);

    $this->assertModelMissing($user);
    $this->assertModelMissing($agen);
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('user endpoints return the documented error for missing or invalid tokens', function (string $method, string $path, string $message, ?string $token) {
    if ($token !== null) {
        $this->withToken($token);
    }

    $this->json($method, $path)->assertUnauthorized()->assertExactJson([
        'data' => [],
        'message' => $message,
    ]);
})->with([
    'get missing' => ['GET', '/api/user', 'Failed get user! User unauthorized.', null],
    'get invalid' => ['GET', '/api/user', 'Failed get user! User unauthorized.', 'invalid'],
    'update missing' => ['PATCH', '/api/user', 'Failed update user! User unauthorized.', null],
    'delete missing' => ['DELETE', '/api/user', 'Failed delete user! User unauthorized.', null],
]);

test('inactive users cannot access their profile endpoints', function () {
    $user = User::factory()->create(['is_active' => false]);
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/user')->assertUnauthorized()
        ->assertJsonPath('message', 'Failed get user! User unauthorized.');
});
