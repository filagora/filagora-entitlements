<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Pages;

use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\UserEntitlementGrantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserEntitlementGrant extends EditRecord
{
    protected static string $resource = UserEntitlementGrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
