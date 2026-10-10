<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\UserEntitlementGrants\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use ForumKit\Entitlements\Filament\Resources\UserEntitlementGrants\UserEntitlementGrantResource;

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
