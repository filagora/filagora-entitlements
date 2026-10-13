<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages;

use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\DefaultEntitlementGrantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDefaultEntitlementGrant extends EditRecord
{
    protected static string $resource = DefaultEntitlementGrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
