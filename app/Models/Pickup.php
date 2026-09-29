<?php

namespace App\Models;

use App\PickupStatus;
use Database\Factories\PickupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $pickup_id
 * @property int $transaction_agent_id
 * @property int $driver_id
 * @property int $agent_id
 * @property Carbon $date
 * @property string $time
 * @property PickupStatus $status
 * @property string $destination
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $driver
 * @property-read User $agent
 * @property-read TransactionAgent $transactionAgent
 */
#[Fillable(['transaction_agent_id', 'driver_id', 'agent_id', 'date', 'time', 'status', 'destination'])]
class Pickup extends Model
{
    /** @use HasFactory<PickupFactory> */
    use HasFactory;

    protected $primaryKey = 'pickup_id';

    /** @return BelongsTo<TransactionAgent, $this> */
    public function transactionAgent(): BelongsTo
    {
        return $this->belongsTo(TransactionAgent::class, 'transaction_agent_id');
    }

    /** @return BelongsTo<User, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
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
            'date' => 'date',
            'status' => PickupStatus::class,
        ];
    }
}
