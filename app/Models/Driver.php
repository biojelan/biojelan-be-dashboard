<?php

namespace App\Models;

use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $driver_id
 * @property string $plate_number
 * @property string $type_vehicle
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['plate_number', 'type_vehicle'])]
class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory;

    protected $primaryKey = 'driver_id';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::saving(function (Driver $driver): void {
            if (! $driver->user()->where('role_id', User::DRIVER_ROLE_ID)->exists()) {
                throw ValidationException::withMessages([
                    'driver_id' => 'Profil driver hanya dapat dimiliki pengguna dengan role driver.',
                ]);
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /** @return HasMany<TransactionAgent, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(TransactionAgent::class, 'driver_id');
    }

    /** @return HasMany<Pickup, $this> */
    public function pickups(): HasMany
    {
        return $this->hasMany(Pickup::class, 'driver_id');
    }
}
