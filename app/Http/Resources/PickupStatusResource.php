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
        ];
    }
}
