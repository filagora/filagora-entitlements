<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Events;

use ForumKit\Entitlements\Models\EntitlementRequest;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a request is rejected by a reviewer.
 */
final class EntitlementRequestRejected
{
    use Dispatchable;

    public function __construct(public readonly EntitlementRequest $request) {}
}
