<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Pages;

use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\UserEntitlementGrantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserEntitlementGrant extends CreateRecord
{
    protected static string $resource = UserEntitlementGrantResource::class;
}
