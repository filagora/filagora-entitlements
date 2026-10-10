<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\EntitlementDefinitionResource;

class EditEntitlementDefinition extends EditRecord
{
    protected static string $resource = EntitlementDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
