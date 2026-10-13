<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Observers;

use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Support\EntitlementCache;

final class BumpUserEntitlementsCacheObserver
{
    public function __construct(private readonly EntitlementCache $cache) {}

    public function saved(UserEntitlementGrant $grant): void
    {
        $this->bump($grant);
    }

    public function deleted(UserEntitlementGrant $grant): void
    {
        $this->bump($grant);
    }

    private function bump(UserEntitlementGrant $grant): void
    {
        $userId = (int) $grant->user_id;

        if ($userId > 0) {
            $this->cache->bumpUserVersion($userId);
        }
    }
}
