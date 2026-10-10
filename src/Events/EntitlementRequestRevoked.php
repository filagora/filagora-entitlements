<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Events;

use ForumKit\Entitlements\Models\EntitlementRequest;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a request is revoked by a reviewer.
 */
final class EntitlementRequestRevoked
{
    use Dispatchable;

    public function __construct(public readonly EntitlementRequest $request) {}
}
