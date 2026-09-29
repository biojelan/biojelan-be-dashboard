<?php

namespace App\Models;

use App\ClientTransactionStatus;
use Database\Factories\TransactionAgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $transaction_id
 * @property int $price_id
 * @property int $agent_id
 * @property int $driver_id
 * @property string $total_price
 * @property string $volume_liter
 * @property ClientTransactionStatus $status
 * @property string|null $transaction_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Price $price
 * @property-read User $agent
 * @property-read User $driver
 * @property-read Pickup|null $pickup
 */
#[Fillable(['price_id', 'agent_id', 'driver_id', 'total_price', 'volume_liter', 'status', 'transaction_note'])]
class TransactionAgent extends Model
{
    /** @use HasFactory<TransactionAgentFactory> */
    use HasFactory;

    protected $table = 'transactions_agent';

    protected static function booted(): void
    {
        static::created(function (TransactionAgent $transaction): void {
            $transaction->forceFill([
                'transaction_id' => sprintf('trx-agent-%03d', $transaction->id),
            ])->saveQuietly();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'transaction_id';
    }

    /** @return BelongsTo<Price, $this> */
    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class, 'price_id', 'price_id');
    }

    /** @return BelongsTo<User, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /** @return BelongsTo<User, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /** @return HasOne<Pickup, $this> */
    public function pickup(): HasOne
    {
        return $this->hasOne(Pickup::class, 'transaction_agent_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'volume_liter' => 'decimal:3',
            'status' => ClientTransactionStatus::class,
        ];
    }
}
