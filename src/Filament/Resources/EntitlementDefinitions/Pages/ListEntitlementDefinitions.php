<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\EntitlementDefinitionResource;

class ListEntitlementDefinitions extends ListRecords
{
    protected static string $resource = EntitlementDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
