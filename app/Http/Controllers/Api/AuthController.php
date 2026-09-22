<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdatePasswordRequest;
use App\Http\Resources\AuthenticationResource;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request): JsonResponse {
                $user = new User($request->safe()->only(['name', 'email', 'password']));
                $user->role_id = 7;
                $user->save();

                return $this->tokenResponse($user, 'Success create user!', 201);
            });
        } catch (UniqueConstraintViolationException $exception) {
            return $this->messageResponse('Failed create user! Email already exists.', 422);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return $this->authenticate($request);
    }

    public function kilangLogin(LoginRequest $request): JsonResponse
    {
        return $this->authenticate($request, kilang: true);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return $this->messageResponse('Success logout!');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! Hash::check((string) $request->string('password'), $user->password)) {
            return $this->messageResponse('Failed update password! Wrong password.', 422);
        }

        DB::transaction(function () use ($user, $request): void {
            $user->forceFill([
                'password' => (string) $request->string('new_password'),
                'remember_token' => Str::random(60),
            ])->save();
            $user->tokens()->delete();
        });

        return $this->messageResponse('Success update password!');
    }

    private function authenticate(LoginRequest $request, bool $kilang = false): JsonResponse
    {
        $user = User::query()->where('email', (string) $request->string('email'))->first();

        if ($user === null) {
            return $this->messageResponse('Failed login! User not registered.', 401);
        }

        if (! Hash::check((string) $request->string('password'), $user->password)) {
            return $this->messageResponse('Failed login! Wrong password.', 401);
        }

        if (! $user->is_active || $user->hasEnabledTwoFactorAuthentication() || ($kilang && ! in_array($user->role_id, [1, 2, 3, 4], true))) {
            return $this->messageResponse('Failed login! User unauthorized.', 403);
        }

        return $this->tokenResponse($user, 'Success login!');
    }

    private function tokenResponse(User $user, string $message, int $status = 200): JsonResponse
    {
        $token = $user->createToken('api', ['*'], now()->addDays(30));

        return (new AuthenticationResource([
            'token' => $token->plainTextToken,
            'name' => $user->name,
            'email' => $user->email,
        ]))->additional(['message' => $message])->response()->setStatusCode($status);
    }

    private function messageResponse(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['data' => (object) [], 'message' => $message], $status);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->safe()->only('email'));

        return match ($status) {
            Password::ResetLinkSent => response()->json([
                'data' => (object) [],
                'message' => 'Success send link reset password to email!',
            ]),
            Password::ResetThrottled => response()->json([
                'data' => (object) [],
                'message' => 'Failed send link reset password! Please wait before retrying.',
            ], 429),
            default => response()->json([
                'data' => (object) [],
                'message' => 'Failed send link reset password! Email not found.',
            ], 422),
        };
    }
}
