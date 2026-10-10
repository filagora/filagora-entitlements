<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Concerns;

use ForumKit\Entitlements\Services\AuthorizationService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * For policies: check entitlements through the central AuthorizationService.
 */
trait AuthorizesWithEntitlements
{
    protected function allows(Authenticatable $user, string $entitlement): bool
    {
        return app(AuthorizationService::class)->allows($user, $entitlement);
    }

    /**
     * @param  list<string>  $entitlements
     */
    protected function allowsAny(Authenticatable $user, array $entitlements): bool
    {
        return app(AuthorizationService::class)->allowsAny($user, $entitlements);
    }

    /**
     * @param  list<string>  $entitlements
     */
    protected function allowsAll(Authenticatable $user, array $entitlements): bool
    {
        return app(AuthorizationService::class)->allowsAll($user, $entitlements);
    }
}
