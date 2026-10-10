<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Support;

/**
 * The Gate abilities that guard the entitlement screens. Each maps to an entitlement
 * key in `forumkit-entitlements.gates`, which a host can point at its own keys.
 */
final class Abilities
{
    /** Create, edit and delete definitions and grants. */
    public const MANAGE = 'forumkit.entitlements.manage';

    /** See and review entitlement requests. */
    public const VIEW = 'forumkit.entitlements.view';
}
