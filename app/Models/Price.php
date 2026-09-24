<?php

namespace App\Models;

use App\PriceType;
use Database\Factories\PriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $price_id
 * @property PriceType $price_type
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string $price_per_liter
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['price_type', 'start_date', 'end_date', 'price_per_liter'])]
class Price extends Model
{
    protected $primaryKey = 'price_id';

    /** @use HasFactory<PriceFactory> */
    use HasFactory;

    /** @return HasMany<TransactionClient, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(TransactionClient::class, 'price_id', 'price_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price_type' => PriceType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'price_per_liter' => 'decimal:2',
        ];
    }
}
