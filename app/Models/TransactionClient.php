<?php

namespace App\Models;

use App\ClientTransactionStatus;
use Database\Factories\TransactionClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $transaction_id
 * @property int $price_id
 * @property int|null $client_id
 * @property int $agent_id
 * @property string $total_price
 * @property string $volume_liter
 * @property ClientTransactionStatus $status
 * @property string|null $transaction_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Price $price
 * @property-read User|null $client
 * @property-read User $agent
 */
#[Fillable(['price_id', 'client_id', 'agent_id', 'total_price', 'volume_liter', 'status', 'transaction_note'])]
class TransactionClient extends Model
{
    /** @use HasFactory<TransactionClientFactory> */
    use HasFactory;

    protected $table = 'transactions_client';

    protected static function booted(): void
    {
        static::created(function (TransactionClient $transaction): void {
            $transaction->forceFill([
                'transaction_id' => sprintf('trx-clients-%03d', $transaction->id),
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
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /** @return BelongsTo<User, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
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
