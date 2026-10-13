<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Models;

use Filagora\Core\Support\Tables;
use Filagora\Core\Support\UserModel;
use Filagora\Entitlements\Enums\EntitlementRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $entitlement_key
 * @property EntitlementRequestStatus $status
 * @property string|null $request_reason
 * @property string|null $review_notes
 * @property int|null $reviewed_by_id
 * @property Carbon|null $requested_at
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $expires_at
 * @property array<string, mixed>|null $meta
 */
class EntitlementRequest extends Model
{
    protected $fillable = [
        'user_id',
        'entitlement_key',
        'status',
        'request_reason',
        'review_notes',
        'reviewed_by_id',
        'requested_at',
        'reviewed_at',
        'expires_at',
        'meta',
    ];

    public function getTable(): string
    {
        return Tables::name('entitlement_requests');
    }

    protected function casts(): array
    {
        return [
            'status' => EntitlementRequestStatus::class,
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
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
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class(), 'reviewed_by_id');
    }

    /**
     * @return MorphMany<UserEntitlementGrant, $this>
     */
    public function grants(): MorphMany
    {
        return $this->morphMany(UserEntitlementGrant::class, 'source');
    }
}
