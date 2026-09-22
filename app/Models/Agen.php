<?php

namespace App\Models;

use Database\Factories\AgenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $agen_id
 * @property string $address
 * @property string $latitude
 * @property string $longitude
 * @property string $bank_name
 * @property string $account_number
 * @property string $open_at
 * @property string $close_at
 * @property list<string> $open_days
 * @property bool $is_open
 * @property float $stock_liter
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['address', 'latitude', 'longitude', 'bank_name', 'account_number', 'open_at', 'close_at', 'open_days', 'is_open', 'stock_liter'])]
class Agen extends Model
{
    /** @use HasFactory<AgenFactory> */
    use HasFactory;

    protected $table = 'agen';

    protected $primaryKey = 'agen_id';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::saving(function (Agen $agen): void {
            if (! $agen->user()->where('role_id', User::AGEN_ROLE_ID)->exists()) {
                throw ValidationException::withMessages([
                    'agen_id' => 'Profil agen hanya dapat dimiliki pengguna dengan role agen.',
                ]);
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agen_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'open_days' => 'array',
            'is_open' => 'boolean',
            'stock_liter' => 'float',
        ];
    }
}
