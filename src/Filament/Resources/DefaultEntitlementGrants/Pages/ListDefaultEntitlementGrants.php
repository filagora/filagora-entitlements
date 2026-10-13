<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\DefaultEntitlementGrantResource;

class ListDefaultEntitlementGrants extends ListRecords
{
    protected static string $resource = DefaultEntitlementGrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
