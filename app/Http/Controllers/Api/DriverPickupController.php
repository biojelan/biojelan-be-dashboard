<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdatePickupStatusRequest;
use App\Http\Resources\PickupStatusResource;
use App\Models\Pickup;
use App\Models\User;
use App\PickupStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverPickupController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $driver */
        $driver = $request->user();
        $pickup = Pickup::query()
            ->with('agent.agen')
            ->where('driver_id', $driver->id)
            ->whereNotIn('status', [PickupStatus::Completed, PickupStatus::Cancelled])
            ->latest('pickup_id')
            ->first();

        if ($pickup === null) {
            return $this->messageResponse('Failed get pickup status! Pickup not found.', 404);
        }

        return (new PickupStatusResource($pickup))
            ->additional(['message' => 'Success get pickup status!'])
            ->response();
    }

    public function update(UpdatePickupStatusRequest $request): JsonResponse
    {
        /** @var User $driver */
        $driver = $request->user();
        $pickup = DB::transaction(function () use ($driver, $request): ?Pickup {
            $pickup = Pickup::query()
                ->whereKey($this->pickupId($request))
                ->where('driver_id', $driver->id)
                ->lockForUpdate()
                ->first();

            if ($pickup === null) {
                return null;
            }

            $pickup->status = PickupStatus::from((string) $request->string('status'));
            $pickup->save();

            return $pickup;
        });

        if ($pickup === null) {
            return $this->messageResponse('Failed update pickup status! Pickup not found.', 404);
        }

        return (new PickupStatusResource($pickup))
            ->additional(['message' => 'Success update pickup status!'])
            ->response();
    }

    private function pickupId(UpdatePickupStatusRequest $request): int
    {
        return (int) preg_replace('/^pkp-/', '', $request->string('pickup_id')->toString());
    }

    private function messageResponse(string $message, int $status): JsonResponse
    {
        return response()->json(['data' => (object) [], 'message' => $message], $status);
    }
}
