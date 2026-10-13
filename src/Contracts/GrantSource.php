<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Contributes entitlement grants from outside the grants tables, for example a
 * memberships add-on. The resolver merges these with default and direct grants.
 *
 * Each returned record has the same shape as the resolver's own: at least
 * `entitlement_key`, `source_kind`, `source_label`, `source_summary`,
 * `source_reference`, `grant_type`, `reason`, `starts_at`, `expires_at` and `metadata`.
 * Records must already be filtered to what is currently active.
 */
interface GrantSource
{
    /**
     * @return list<array<string, mixed>>
     */
    public function grantsFor(Model $user): array;
}
