<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Observers;

use ForumKit\Entitlements\Models\DefaultEntitlementGrant;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Services\EntitlementDefinitionCatalog;

/**
 * Makes sure a definition exists for every key a grant refers to.
 */
final class SyncEntitlementDefinitionObserver
{
    public function __construct(private readonly EntitlementDefinitionCatalog $catalog) {}

    public function saved(DefaultEntitlementGrant|UserEntitlementGrant $grant): void
    {
        $this->catalog->synchronizeKey($grant->entitlement_key);
    }
}
