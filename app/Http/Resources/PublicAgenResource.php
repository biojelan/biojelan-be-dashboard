<?php

namespace App\Http\Resources;

use App\Models\Agen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Agen */
class PublicAgenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'agen_id' => $this->agen_id,
            'role_id' => $this->user->role_id,
            'name' => $this->user->name,
            'phone' => $this->user->phone,
            'address' => $this->address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'open_at' => substr((string) $this->open_at, 0, 5),
            'close_at' => substr((string) $this->close_at, 0, 5),
            'is_open' => $this->is_open,
            'open_days' => $this->open_days,
        ];
    }
}
