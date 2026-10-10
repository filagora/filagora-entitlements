<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\UserEntitlementGrants\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use ForumKit\Entitlements\Filament\Resources\UserEntitlementGrants\UserEntitlementGrantResource;

class ListUserEntitlementGrants extends ListRecords
{
    protected static string $resource = UserEntitlementGrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
