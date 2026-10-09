<?php

namespace App\Models;

use Database\Factories\FactoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $factory_id
 * @property string $factory_name
 * @property string $address
 * @property float $latitude
 * @property float $longitude
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Admin> $admins
 */
#[Fillable(['factory_name', 'address', 'latitude', 'longitude'])]
class Factory extends Model
{
    /** @use HasFactory<FactoryFactory> */
    use HasFactory;

    protected $table = 'factory';

    protected $primaryKey = 'factory_id';

    /** @return HasMany<Admin, $this> */
    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class, 'factory_id', 'factory_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
