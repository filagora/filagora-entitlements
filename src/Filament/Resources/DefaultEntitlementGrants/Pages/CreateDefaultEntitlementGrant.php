<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages;

use Filament\Resources\Pages\CreateRecord;
use ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\DefaultEntitlementGrantResource;

class CreateDefaultEntitlementGrant extends CreateRecord
{
    protected static string $resource = DefaultEntitlementGrantResource::class;
}
