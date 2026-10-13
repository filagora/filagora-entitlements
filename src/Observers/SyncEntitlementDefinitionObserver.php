<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Observers;

use Filagora\Entitlements\Models\DefaultEntitlementGrant;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Services\EntitlementDefinitionCatalog;

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
