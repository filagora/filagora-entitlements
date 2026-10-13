<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\EntitlementRequests\Pages;

use Filagora\Entitlements\Filament\Actions\ReviewActions;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\EntitlementRequestResource;
use Filagora\Entitlements\Models\EntitlementRequest;
use Filament\Resources\Pages\ViewRecord;

class ViewEntitlementRequest extends ViewRecord
{
    protected static string $resource = EntitlementRequestResource::class;

    protected function getHeaderActions(): array
    {
        return ReviewActions::all(
            record: function (): EntitlementRequest {
                assert($this->record instanceof EntitlementRequest);

                return $this->record;
            },
            onReviewed: function (EntitlementRequest $reviewed): void {
                $this->record = $reviewed;
            },
        );
    }
}
