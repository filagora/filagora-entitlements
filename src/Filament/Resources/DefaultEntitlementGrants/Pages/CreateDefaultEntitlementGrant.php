<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\DefaultEntitlementGrantResource;

class CreateDefaultEntitlementGrant extends CreateRecord
{
    protected static string $resource = DefaultEntitlementGrantResource::class;
}
