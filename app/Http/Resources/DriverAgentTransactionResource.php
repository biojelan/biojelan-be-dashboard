<?php

namespace App\Http\Resources;

use App\Models\TransactionAgent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TransactionAgent */
class DriverAgentTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'transaction_id' => $this->transaction_id,
            'driver_id' => $this->driver_id,
            'agent_id' => $this->agent_id,
            'agent_name' => $this->agent->name,
            'volume_liter' => (float) $this->volume_liter,
            'price' => (float) $this->price->price_per_liter,
            'total_price' => (float) $this->total_price,
            'status' => $this->status->value,
            'transaction_note' => $this->transaction_note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
