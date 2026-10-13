<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Registry;

/**
 * An entitlement a package declares. Synced into the definitions table by
 * `filagora:entitlements:sync`.
 */
final readonly class Definition
{
    public function __construct(
        public string $key,
        public ?string $label = null,
        public ?string $description = null,
        public bool $grantable = true,
        public bool $requestable = false,
        public bool $wildcardChildren = true,
        public int $sortOrder = 0,
    ) {}
}
