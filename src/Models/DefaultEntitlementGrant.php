<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Models;

use ForumKit\Core\Support\Tables;
use ForumKit\Entitlements\Enums\DefaultEntitlementTarget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $entitlement_key
 * @property DefaultEntitlementTarget $target_type
 * @property string|null $target_value
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property string|null $reason
 * @property array<string, mixed>|null $meta
 */
class DefaultEntitlementGrant extends Model
{
    protected $fillable = [
        'entitlement_key',
        'target_type',
        'target_value',
        'starts_at',
        'expires_at',
        'reason',
        'meta',
    ];

    public function getTable(): string
    {
        return Tables::name('default_entitlement_grants');
    }

    protected function casts(): array
    {
        return [
            'target_type' => DefaultEntitlementTarget::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
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
