<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages;

use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\DefaultEntitlementGrantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDefaultEntitlementGrant extends CreateRecord
{
    protected static string $resource = DefaultEntitlementGrantResource::class;
}
