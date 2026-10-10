<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Pages;

use Filament\Resources\Pages\CreateRecord;
use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\EntitlementDefinitionResource;

class CreateEntitlementDefinition extends CreateRecord
{
    protected static string $resource = EntitlementDefinitionResource::class;
}
