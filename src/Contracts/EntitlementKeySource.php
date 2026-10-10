<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Contracts;

/**
 * Lists the entitlement keys a package refers to (for example the keys set on its
 * forums), so the definition catalogue can create definitions for them.
 */
interface EntitlementKeySource
{
    /**
     * @return list<string>
     */
    public function keys(): array;
}
