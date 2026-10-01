<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PickupStatusResource;
use App\Models\Pickup;
use App\Models\User;
use App\PickupStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentPickupController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $agent */
        $agent = $request->user();
        $pickup = Pickup::query()
            ->where('agent_id', $agent->id)
            ->whereNotIn('status', [PickupStatus::Completed, PickupStatus::Cancelled])
            ->latest('pickup_id')
            ->first();

        if ($pickup === null) {
            return response()->json([
                'data' => (object) [],
                'message' => 'Failed get pickup status! Pickup not found.',
            ], 404);
        }

        return (new PickupStatusResource($pickup))
            ->additional(['message' => 'Success get pickup status!'])
            ->response();
    }
}
