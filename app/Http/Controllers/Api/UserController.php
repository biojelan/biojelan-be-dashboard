<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\PublicAgenResource;
use App\Http\Resources\UserResource;
use App\Models\Agen;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return (new UserResource($user->load('agen')))
            ->additional(['message' => 'Success get user!'])
            ->response();
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            $user->fill($request->safe()->only(['name', 'email', 'phone']));
            $user->save();

            $agenAttributes = $request->safe()->input('agen');

            if (is_array($agenAttributes)) {
                $agen = $user->agen()->firstOrNew();
                $agen->fill(Arr::only($agenAttributes, [
                    'address', 'latitude', 'longitude', 'bank_name', 'account_number',
                    'open_at', 'close_at', 'open_days', 'is_open',
                ]));
                $agen->save();
            }
        });

        return (new UserResource($user->fresh()->load('agen')))
            ->additional(['message' => 'Success update user!'])
            ->response();
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json([
            'data' => (object) [],
            'message' => 'Success delete user!',
        ]);
    }

    public function agen(): JsonResponse
    {
        $agen = Agen::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query
                ->where('role_id', User::AGEN_ROLE_ID)
                ->where('is_active', true))
            ->get();

        return PublicAgenResource::collection($agen)
            ->additional(['message' => 'Success get user agen!'])
            ->response();
    }
}
