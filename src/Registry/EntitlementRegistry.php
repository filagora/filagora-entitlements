<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Registry;

use ForumKit\Entitlements\Contracts\EntitlementKeySource;
use ForumKit\Entitlements\Contracts\GrantSource;

/**
 * What packages declare to the entitlements package: the entitlement keys they own,
 * sources of further keys, and sources of grants that live outside the grant tables.
 */
final class EntitlementRegistry
{
    /** @var array<string, Definition> */
    private array $definitions = [];

    /** @var list<EntitlementKeySource> */
    private array $keySources = [];

    /** @var list<GrantSource> */
    private array $grantSources = [];

    public function define(Definition $definition): void
    {
        $this->definitions[$definition->key] = $definition;
    }

    /**
     * @return array<string, Definition>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }

    public function keySource(EntitlementKeySource $source): void
    {
        $this->keySources[] = $source;
    }

    /**
     * @return list<EntitlementKeySource>
     */
    public function keySources(): array
    {
        return $this->keySources;
    }

    public function grantSource(GrantSource $source): void
    {
        $this->grantSources[] = $source;
    }

    /**
     * @return list<GrantSource>
     */
    public function grantSources(): array
    {
        return $this->grantSources;
    }
}
