<?php

namespace App\Http\Resources;

use App\Models\TransactionClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TransactionClient */
class ClientTransactionResource extends JsonResource
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
            'agen_id' => $this->agent_id,
            'client_id' => $this->client_id,
            'agen_name' => $this->agent->name,
            'volume_liter' => (float) $this->volume_liter,
            'price' => (float) $this->price->price_per_liter,
            'total_price' => (float) $this->total_price,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
