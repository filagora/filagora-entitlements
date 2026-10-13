<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Events;

use Filagora\Entitlements\Models\EntitlementRequest;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a request is approved by a reviewer.
 */
final class EntitlementRequestApproved
{
    use Dispatchable;

    public function __construct(public readonly EntitlementRequest $request) {}
}
