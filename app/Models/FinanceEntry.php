<?php

namespace App\Models;

use Database\Factories\FinanceEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $factory_id
 * @property string $type
 * @property string $amount
 * @property string|null $volume_liter
 * @property Carbon $date
 * @property string $description
 */
#[Fillable(['factory_id', 'created_by', 'type', 'date', 'amount', 'volume_liter', 'description'])]
class FinanceEntry extends Model
{
    /** @use HasFactory<FinanceEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['date' => 'date', 'amount' => 'decimal:2', 'volume_liter' => 'decimal:3'];
    }

    /** @return BelongsTo<Factory, $this> */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'factory_id', 'factory_id');
    }
}
