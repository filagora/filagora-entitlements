<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\EntitlementRequests\Pages;

use Filament\Resources\Pages\ViewRecord;
use ForumKit\Entitlements\Filament\Actions\ReviewActions;
use ForumKit\Entitlements\Filament\Resources\EntitlementRequests\EntitlementRequestResource;
use ForumKit\Entitlements\Models\EntitlementRequest;

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
