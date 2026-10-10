<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Concerns;

use ForumKit\Entitlements\Models\EntitlementRequest;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Services\EntitlementResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Optional trait for the host's user model: entitlement relations and shortcuts.
 * Nothing in the package requires it; the relations are also reachable through the
 * package's own models.
 *
 * @mixin Model
 */
trait HasEntitlements
{
    /**
     * @return HasMany<UserEntitlementGrant, $this>
     */
    public function entitlementGrants(): HasMany
    {
        return $this->hasMany(UserEntitlementGrant::class, 'user_id');
    }

    /**
     * @return HasMany<EntitlementRequest, $this>
     */
    public function entitlementRequests(): HasMany
    {
        return $this->hasMany(EntitlementRequest::class, 'user_id');
    }

    /**
     * @return HasMany<EntitlementRequest, $this>
     */
    public function reviewedEntitlementRequests(): HasMany
    {
        return $this->hasMany(EntitlementRequest::class, 'reviewed_by_id');
    }

    public function hasEntitlement(string $entitlement): bool
    {
        return app(EntitlementResolver::class)->hasEntitlement($this, $entitlement);
    }
}
