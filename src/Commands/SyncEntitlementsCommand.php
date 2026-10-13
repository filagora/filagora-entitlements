<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Commands;

use Filagora\Entitlements\Services\EntitlementDefinitionCatalog;
use Illuminate\Console\Command;

class SyncEntitlementsCommand extends Command
{
    protected $signature = 'filagora:entitlements:sync';

    protected $description = 'Create entitlement definitions for every key declared by packages or used by grants';

    public function handle(EntitlementDefinitionCatalog $catalog): int
    {
        $keys = $catalog->synchronizeAll();

        $this->info(count($keys).' entitlement definition(s) in sync.');

        return self::SUCCESS;
    }
}
