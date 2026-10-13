<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\EntitlementRequests\Pages;

use Filagora\Entitlements\Filament\Resources\EntitlementRequests\EntitlementRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListEntitlementRequests extends ListRecords
{
    protected static string $resource = EntitlementRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
