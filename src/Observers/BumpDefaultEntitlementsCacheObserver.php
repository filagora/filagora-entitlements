<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Observers;

use Filagora\Entitlements\Support\EntitlementCache;

final class BumpDefaultEntitlementsCacheObserver
{
    public function __construct(private readonly EntitlementCache $cache) {}

    public function saved(mixed $model): void
    {
        $this->cache->bumpDefaultVersion();
    }

    public function deleted(mixed $model): void
    {
        $this->cache->bumpDefaultVersion();
    }
}
