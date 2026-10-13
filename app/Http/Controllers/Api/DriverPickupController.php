<?php

namespace App\Http\Controllers\Api;

use App\ClientTransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdatePickupStatusRequest;
use App\Http\Resources\PickupStatusResource;
use App\Models\Factory;
use App\Models\Pickup;
use App\Models\TransactionAgent;
use App\Models\User;
use App\PickupStatus;
use Illuminate\Http\Exceptions\HttpResponseException;
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

        if ($pickup?->route_key !== null) {
            $pickup = Pickup::query()->with('agent.agen')->where('driver_id', $driver->id)
                ->where('route_key', $pickup->route_key)->whereNotIn('status', [PickupStatus::Completed, PickupStatus::Cancelled])
                ->orderBy('stop_order')->first();
        }

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
                ->first();

            if ($pickup === null) {
                return null;
            }

            $transaction = TransactionAgent::query()->lockForUpdate()->findOrFail($pickup->transaction_agent_id);
            $pickup = Pickup::query()->lockForUpdate()->findOrFail($pickup->pickup_id);

            $nextStatus = PickupStatus::from((string) $request->string('status'));
            if (in_array($pickup->status, [PickupStatus::Completed, PickupStatus::Cancelled], true) && $pickup->status !== $nextStatus) {
                throw new HttpResponseException($this->messageResponse('Failed update pickup status! Pickup is already final.', 422));
            }
            if ($nextStatus === PickupStatus::Completed && $transaction->status !== ClientTransactionStatus::Accepted) {
                throw new HttpResponseException($this->messageResponse('Failed update pickup status! Transaction must be accepted first.', 422));
            }
            if ($nextStatus === PickupStatus::Completed) {
                $pickup->completed_at ??= now();
                $pickup->factory_id ??= Factory::query()->oldest('factory_id')->value('factory_id');
            }
            $pickup->status = $nextStatus;
            $pickup->save();

            return $pickup;
        }, 3);

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
