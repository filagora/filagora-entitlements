<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\UserEntitlementGrantResource;

class CreateUserEntitlementGrant extends CreateRecord
{
    protected static string $resource = UserEntitlementGrantResource::class;
}
