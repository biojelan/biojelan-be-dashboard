<?php

namespace App\Models;

use Database\Factories\AgentApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property array<string, mixed> $profile
 * @property string $status
 * @property-read User $user
 */
#[Fillable(['user_id', 'profile', 'status', 'reviewed_by', 'reviewed_at', 'review_note'])]
class AgentApplication extends Model
{
    /** @use HasFactory<AgentApplicationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['profile' => 'array', 'reviewed_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
