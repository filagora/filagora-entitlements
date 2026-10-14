<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Privacy;

use Filagora\Core\Contracts\PrivacyContributor;
use Filagora\Entitlements\Models\EntitlementRequest;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Illuminate\Database\Eloquent\Model;

/**
 * A person's entitlement grants and requests, for the compliance package's export and erasure.
 * Erasing deletes them one by one, so each deletion clears the cached entitlements as it does
 * everywhere else.
 */
final class EntitlementsDataContributor implements PrivacyContributor
{
    public function key(): string
    {
        return 'entitlements';
    }

    public function export(Model $user): array
    {
        return [
            'grants' => UserEntitlementGrant::query()->where('user_id', $user->getKey())->orderBy('id')
                ->get(['entitlement_key', 'grant_type', 'source_type', 'starts_at', 'expires_at', 'revoked_at', 'reason', 'created_at'])->toArray(),
            'requests' => EntitlementRequest::query()->where('user_id', $user->getKey())->orderBy('id')
                ->get(['entitlement_key', 'status', 'request_reason', 'review_notes', 'requested_at', 'reviewed_at', 'expires_at'])->toArray(),
        ];
    }

    public function erase(Model $user): void
    {
        UserEntitlementGrant::query()->where('user_id', $user->getKey())->get()->each->delete();
        EntitlementRequest::query()->where('user_id', $user->getKey())->get()->each->delete();
    }
}
