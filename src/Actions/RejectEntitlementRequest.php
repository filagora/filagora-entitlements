<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Actions;

use Filagora\Entitlements\Enums\EntitlementRequestStatus;
use Filagora\Entitlements\Events\EntitlementRequestRejected;
use Filagora\Entitlements\Models\EntitlementRequest;
use Illuminate\Database\Eloquent\Model;

class RejectEntitlementRequest
{
    public function __invoke(EntitlementRequest $request, Model $reviewer, ?string $reviewNotes = null): EntitlementRequest
    {
        $request->status = EntitlementRequestStatus::REJECTED;
        $request->reviewed_by_id = $reviewer->getKey();
        $request->reviewed_at = now();
        $request->review_notes = $this->normalizeNotes($reviewNotes);
        $request->save();

        $rejected = $request->refresh();

        EntitlementRequestRejected::dispatch($rejected);

        return $rejected;
    }

    private function normalizeNotes(?string $reviewNotes): ?string
    {
        $normalized = trim((string) $reviewNotes);

        return $normalized === '' ? null : $normalized;
    }
}
