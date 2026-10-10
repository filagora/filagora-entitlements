<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Actions;

use ForumKit\Entitlements\Enums\EntitlementRequestStatus;
use ForumKit\Entitlements\Events\EntitlementRequestRevoked;
use ForumKit\Entitlements\Models\EntitlementRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RevokeEntitlementRequest
{
    /**
     * Revoke a request: revoke every still-active grant it created and mark it revoked.
     */
    public function __invoke(EntitlementRequest $request, Model $reviewer, ?string $reviewNotes = null): EntitlementRequest
    {
        $revoked = DB::transaction(function () use ($request, $reviewer, $reviewNotes): EntitlementRequest {
            $notes = $this->normalizeNotes($reviewNotes);

            foreach ($request->grants()->whereNull('revoked_at')->get() as $grant) {
                $grant->revoked_at = now();
                $grant->revoked_by_id = $reviewer->getKey();
                $grant->reason = $notes ?: $grant->reason;
                $grant->save();
            }

            $request->status = EntitlementRequestStatus::REVOKED;
            $request->reviewed_by_id = $reviewer->getKey();
            $request->reviewed_at = now();
            $request->review_notes = $notes;
            $request->save();

            return $request->refresh();
        });

        EntitlementRequestRevoked::dispatch($revoked);

        return $revoked;
    }

    private function normalizeNotes(?string $reviewNotes): ?string
    {
        $normalized = trim((string) $reviewNotes);

        return $normalized === '' ? null : $normalized;
    }
}
