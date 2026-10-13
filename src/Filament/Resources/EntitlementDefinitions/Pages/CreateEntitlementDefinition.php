<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\EntitlementDefinitions\Pages;

use Filagora\Entitlements\Filament\Resources\EntitlementDefinitions\EntitlementDefinitionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEntitlementDefinition extends CreateRecord
{
    protected static string $resource = EntitlementDefinitionResource::class;
}
