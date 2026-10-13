<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\EntitlementDefinitions\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filagora\Entitlements\Filament\Resources\EntitlementDefinitions\EntitlementDefinitionResource;

class CreateEntitlementDefinition extends CreateRecord
{
    protected static string $resource = EntitlementDefinitionResource::class;
}
