<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Events;

use Filagora\Entitlements\Models\EntitlementRequest;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a request is revoked by a reviewer.
 */
final class EntitlementRequestRevoked
{
    use Dispatchable;

    public function __construct(public readonly EntitlementRequest $request) {}
}
