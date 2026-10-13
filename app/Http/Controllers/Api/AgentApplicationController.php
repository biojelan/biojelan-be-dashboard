<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAgentApplicationRequest;
use App\Models\AgentApplication;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentApplicationController extends Controller
{
    public function store(StoreAgentApplicationRequest $request): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        $application = DB::transaction(function () use ($user, $request): ?AgentApplication {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($user->role_id === User::CLIENT_ROLE_ID, 403);
            $application = AgentApplication::query()->where('user_id', $user->id)->first();
            if ($application !== null && $application->status !== 'REJECTED') {
                return null;
            }

            return AgentApplication::query()->updateOrCreate(['user_id' => $user->id], [
                'profile' => $request->validated('profile'), 'status' => 'PENDING', 'reviewed_at' => null, 'reviewed_by' => null, 'review_note' => null,
            ]);
        });
        if ($application === null) {
            return response()->json(['data' => (object) [], 'message' => 'Failed submit agent request! Request already exists.'], 422);
        }

        return response()->json(['data' => $application->only(['id', 'status', 'created_at']), 'message' => 'Success submit agent request!'], 201);
    }

    public function show(Request $request): JsonResponse
    {
        $application = AgentApplication::query()->where('user_id', $request->user()->id)->first();

        return response()->json(['data' => $application?->only(['id', 'profile', 'status', 'review_note', 'reviewed_at', 'created_at']) ?? (object) [], 'message' => 'Success get agent request!']);
    }
}
