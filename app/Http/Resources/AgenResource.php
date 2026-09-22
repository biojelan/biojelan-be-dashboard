<?php

namespace App\Http\Resources;

use App\Models\Agen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Agen */
class AgenResource extends JsonResource
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
            'address' => $this->address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'open_at' => substr((string) $this->open_at, 0, 5),
            'close_at' => substr((string) $this->close_at, 0, 5),
            'open_days' => $this->open_days,
            'is_open' => $this->is_open,
            'stock_liter' => $this->stock_liter,
        ];
    }
}
