<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;

test('forgot password sends a reset link for a registered email', function () {
    Notification::fake();
    $user = User::factory()->create();

    $response = $this->postJson('/api/forgot-password', ['email' => $user->email]);

    $response->assertOk()->assertExactJson([
        'data' => [],
        'message' => 'Success send link reset password to email!',
    ]);
    Notification::assertSentTo($user, ResetPassword::class);
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    expect(json_decode($response->getContent())->data)->toBeInstanceOf(stdClass::class);
});

test('forgot password returns the documented error for an unknown email', function () {
    Notification::fake();

    $this->postJson('/api/forgot-password', ['email' => 'missing@mail.com'])
        ->assertUnprocessable()->assertExactJson([
            'data' => [],
            'message' => 'Failed send link reset password! Email not found.',
        ]);

    Notification::assertNothingSent();
});

test('forgot password throttles repeated reset emails', function () {
    Notification::fake();
    $user = User::factory()->create();
    $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

    $this->postJson('/api/forgot-password', ['email' => $user->email])
        ->assertTooManyRequests()->assertJsonPath('message', 'Failed send link reset password! Please wait before retrying.');

    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});

test('forgot password rejects invalid emails without sending a notification', function (mixed $email) {
    Notification::fake();

    $this->postJson('/api/forgot-password', ['email' => $email])
        ->assertUnprocessable()->assertExactJson([
            'data' => [],
            'message' => 'Failed send link reset password! Invalid email.',
        ]);

    Notification::assertNothingSent();
})->with(['missing' => null, 'malformed' => 'invalid', 'array' => [['invalid']]]);

test('authentication requests are rate limited', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/forgot-password', ['email' => 'missing@mail.com'])->assertUnprocessable();
    }

    $this->postJson('/api/forgot-password', ['email' => 'missing@mail.com'])
        ->assertTooManyRequests()->assertJsonPath('message', 'Too many requests. Please try again later.');
});

test('registration creates a client and returns a usable bearer token', function () {
    $this->seed(RoleSeeder::class);

    $response = $this->postJson('/api/register', [
        'name' => 'Postman Client',
        'email' => 'client@mail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role_id' => 1,
        'is_verified' => true,
        'is_active' => false,
    ]);

    $response->assertCreated()->assertJsonPath('message', 'Success create user!')
        ->assertJsonStructure(['data' => ['token', 'name', 'email']]);
    $user = User::query()->where('email', 'client@mail.com')->firstOrFail();
    expect($user->role_id)->toBe(7);
    expect($user->is_verified)->toBeFalse();
    expect($user->is_active)->toBeTrue();
    expect(Hash::check('password123', $user->password))->toBeTrue();
    $this->assertDatabaseCount('personal_access_tokens', 1);
    $this->withToken($response->json('data.token'))->deleteJson('/api/logout')->assertOk();
});

test('registration rejects duplicate email addresses', function () {
    $user = User::factory()->create();

    $this->postJson('/api/register', [
        'name' => 'Duplicate', 'email' => $user->email,
        'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertUnprocessable()->assertJsonPath('message', 'Failed create user! Email already exists.');

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('registration rejects invalid input without creating an account', function (array $overrides, string $message) {
    $this->postJson('/api/register', array_replace([
        'name' => 'Client', 'email' => 'client@mail.com',
        'password' => 'password123', 'password_confirmation' => 'password123',
    ], $overrides))->assertUnprocessable()->assertJsonPath('message', $message);

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('personal_access_tokens', 0);
})->with([
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'Failed create user! Invalid password.'],
    'confirmation mismatch' => [['password_confirmation' => 'different'], 'Failed create user! Invalid password.'],
    'missing name' => [['name' => null], 'Failed create user! Invalid name.'],
    'invalid email' => [['email' => 'invalid'], 'Failed create user! Invalid email.'],
]);

test('login issues a token with the documented response', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123']);

    $response->assertOk()->assertJsonPath('data.name', $user->name)
        ->assertJsonPath('data.email', $user->email)->assertJsonPath('message', 'Success login!');
    $token = PersonalAccessToken::findToken($response->json('data.token'));
    expect($token->tokenable_id)->toBe($user->id);
    expect($token->token)->not->toBe($response->json('data.token'));
    expect($token->expires_at)->not->toBeNull();
});

test('login returns the documented credential errors', function (bool $exists, string $message) {
    if ($exists) {
        User::factory()->create(['email' => 'user@mail.com']);
    }

    $this->postJson('/api/login', ['email' => 'user@mail.com', 'password' => 'wrong-password'])
        ->assertUnauthorized()->assertJsonPath('message', $message);

    $this->assertDatabaseCount('personal_access_tokens', 0);
})->with([
    'unregistered' => [false, 'Failed login! User not registered.'],
    'wrong password' => [true, 'Failed login! Wrong password.'],
]);

test('inactive users cannot login', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertForbidden()->assertJsonPath('message', 'Failed login! User unauthorized.');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('password-only API login cannot bypass enabled two factor authentication', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertForbidden();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('kilang login allows only the four dashboard roles', function (int $roleId, bool $allowed) {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['role_id' => $roleId]);

    $response = $this->postJson('/api/kilang-login', ['email' => $user->email, 'password' => 'password']);

    $response->assertStatus($allowed ? 200 : 403)
        ->assertJsonPath('message', $allowed ? 'Success login!' : 'Failed login! User unauthorized.');
    $this->assertDatabaseCount('personal_access_tokens', $allowed ? 1 : 0);
})->with([
    'superadmin' => [1, true], 'admin' => [2, true], 'finance' => [3, true],
    'operasional' => [4, true], 'driver' => [5, false], 'agen' => [6, false], 'klien' => [7, false],
]);

test('logout revokes only the current token and rejects reuse', function () {
    $user = User::factory()->create();
    $token = $user->createToken('current')->plainTextToken;
    $other = $user->createToken('other');

    $response = $this->withToken($token)->deleteJson('/api/logout');

    $response->assertOk()->assertExactJson(['data' => [], 'message' => 'Success logout!']);
    $this->assertModelExists($other->accessToken);
    $this->assertDatabaseCount('personal_access_tokens', 1);
    $this->app->make('auth')->forgetGuards();
    $this->withToken($token)->deleteJson('/api/logout')->assertUnauthorized()
        ->assertJsonPath('message', 'Failed logout! User unauthorized.');
});

test('protected endpoints reject missing or invalid bearer tokens', function (string $method, string $path, string $message, ?string $token) {
    if ($token !== null) {
        $this->withToken($token);
    }

    $this->json($method, $path)->assertUnauthorized()->assertExactJson(['data' => [], 'message' => $message]);
})->with([
    'logout missing' => ['DELETE', '/api/logout', 'Failed logout! User unauthorized.', null],
    'logout invalid' => ['DELETE', '/api/logout', 'Failed logout! User unauthorized.', 'invalid'],
    'password missing' => ['PATCH', '/api/update-password', 'Failed update password! User unauthorized.', null],
    'password invalid' => ['PATCH', '/api/update-password', 'Failed update password! User unauthorized.', 'invalid'],
]);

test('expired tokens and inactive users cannot access protected endpoints', function (bool $active) {
    $user = User::factory()->create(['is_active' => $active]);
    $token = $user->createToken('api', ['*'], $active ? now()->subMinute() : now()->addDay());

    $this->withToken($token->plainTextToken)->deleteJson('/api/logout')->assertUnauthorized();
})->with(['expired' => true, 'inactive' => false]);

test('web authentication cannot replace the required bearer token', function () {
    $this->actingAs(User::factory()->create());

    $this->deleteJson('/api/logout')->assertUnauthorized();
});

test('updating password hashes the new password and revokes all user tokens', function () {
    $user = User::factory()->create();
    $token = $user->createToken('current')->plainTextToken;
    $user->createToken('other');

    $this->withToken($token)->patchJson('/api/update-password', [
        'password' => 'password', 'new_password' => 'new-password123', 'password_confirmation' => 'new-password123',
    ])->assertOk()->assertExactJson(['data' => [], 'message' => 'Success update password!']);

    expect(Hash::check('new-password123', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('password update failures preserve the original password and tokens', function (array $overrides, string $message) {
    $user = User::factory()->create();
    $token = $user->createToken('current')->plainTextToken;
    $originalPassword = $user->password;

    $this->withToken($token)->patchJson('/api/update-password', array_replace([
        'password' => 'password', 'new_password' => 'new-password123', 'password_confirmation' => 'new-password123',
    ], $overrides))->assertUnprocessable()->assertJsonPath('message', $message);

    expect($user->fresh()->password)->toBe($originalPassword);
    $this->assertDatabaseCount('personal_access_tokens', 1);
})->with([
    'wrong current password' => [['password' => 'wrong'], 'Failed update password! Wrong password.'],
    'confirmation mismatch' => [['password_confirmation' => 'different'], 'Failed update password! Invalid password confirmation.'],
    'short new password' => [['new_password' => 'short', 'password_confirmation' => 'short'], 'Failed update password! Invalid password.'],
]);

test('resetting a forgotten password also revokes API tokens', function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->createToken('api');
    $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
    $notification = Notification::sent($user, ResetPassword::class)->first();

    $this->post('/reset-password', [
        'email' => $user->email,
        'token' => $notification->token,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('new-password123', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseCount('personal_access_tokens', 0);
});
