<?php

namespace App\Http\Resources;

use App\Models\Pickup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pickup */
class PickupStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'pickup_id' => sprintf('pkp-%03d', $this->pickup_id),
            'status' => $this->status->value,
            'updated_at' => $this->updated_at,
            'driver' => $this->when($this->relationLoaded('driver'), fn (): array => [
                'driver_id' => $this->driver_id,
                'name' => $this->driver?->name,
                'phone' => $this->driver?->phone,
                'plate_number' => $this->driver?->driver?->plate_number,
                'type_vehicle' => $this->driver?->driver?->type_vehicle,
            ]),
            'agent' => $this->when($this->relationLoaded('agent'), fn (): array => [
                'agent_id' => $this->agent_id,
                'name' => $this->agent?->name,
                'phone' => $this->agent?->phone,
                'address' => $this->agent?->agen?->address,
                'latitude' => $this->agent?->agen === null ? null : (float) $this->agent->agen->latitude,
                'longitude' => $this->agent?->agen === null ? null : (float) $this->agent->agen->longitude,
            ]),
        ];
    }
}
