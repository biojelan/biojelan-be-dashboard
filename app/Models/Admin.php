<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $admin_id
 * @property int $factory_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Factory $factory
 */
class Admin extends Model
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    protected $table = 'admin';

    protected $primaryKey = 'admin_id';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::saving(function (Admin $admin): void {
            if (! $admin->user()->where('role_id', User::ADMIN_ROLE_ID)->exists()) {
                throw ValidationException::withMessages([
                    'admin_id' => 'Profil admin hanya dapat dimiliki pengguna dengan role admin.',
                ]);
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** @return BelongsTo<Factory, $this> */
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'factory_id', 'factory_id');
    }
}
