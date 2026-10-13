<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Models;

use Filagora\Core\Support\Tables;
use Filagora\Core\Support\UserModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $entitlement_key
 * @property string $grant_type
 * @property string|null $source_type
 * @property int|null $source_id
 * @property int|null $granted_by_id
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property int|null $revoked_by_id
 * @property string|null $reason
 * @property array<string, mixed>|null $meta
 */
class UserEntitlementGrant extends Model
{
    protected $fillable = [
        'user_id',
        'entitlement_key',
        'grant_type',
        'source_type',
        'source_id',
        'granted_by_id',
        'starts_at',
        'expires_at',
        'revoked_at',
        'revoked_by_id',
        'reason',
        'meta',
    ];

    public function getTable(): string
    {
        return Tables::name('user_entitlement_grants');
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class());
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class(), 'granted_by_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class(), 'revoked_by_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function (Builder $inner): void {
                $inner->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $inner): void {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
