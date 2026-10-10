<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Actions;

use ForumKit\Entitlements\Enums\EntitlementRequestStatus;
use ForumKit\Entitlements\Events\EntitlementRequestApproved;
use ForumKit\Entitlements\Models\EntitlementRequest;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApproveEntitlementRequest
{
    /**
     * Approve a request: create (or revive) the grant it asks for and mark it approved.
     */
    public function __invoke(
        EntitlementRequest $request,
        Model $reviewer,
        ?string $reviewNotes = null,
        mixed $expiresAt = null,
    ): EntitlementRequest {
        $approved = DB::transaction(function () use ($request, $reviewer, $reviewNotes, $expiresAt): EntitlementRequest {
            $grant = UserEntitlementGrant::query()->firstOrNew([
                'user_id' => $request->user_id,
                'entitlement_key' => $request->entitlement_key,
                'grant_type' => 'approved_request',
                'source_type' => EntitlementRequest::class,
                'source_id' => $request->id,
            ]);

            $notes = $this->normalizeNotes($reviewNotes);

            $grant->granted_by_id = $reviewer->getKey();
            $grant->starts_at ??= now();
            $grant->expires_at = $expiresAt;
            $grant->revoked_at = null;
            $grant->revoked_by_id = null;
            $grant->reason = $notes ?: $grant->reason;
            $grant->save();

            $request->status = EntitlementRequestStatus::APPROVED;
            $request->reviewed_by_id = $reviewer->getKey();
            $request->reviewed_at = now();
            $request->expires_at = $expiresAt;
            $request->review_notes = $notes;
            $request->save();

            return $request->refresh();
        });

        EntitlementRequestApproved::dispatch($approved);

        return $approved;
    }

    private function normalizeNotes(?string $reviewNotes): ?string
    {
        $normalized = trim((string) $reviewNotes);

        return $normalized === '' ? null : $normalized;
    }
}
