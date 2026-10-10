<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\DefaultEntitlementGrantResource;

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
