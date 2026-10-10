<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Resolver cache lifetime
    |--------------------------------------------------------------------------
    |
    | Seconds a user's resolved entitlements are cached (minimum 30). Changing a
    | grant bumps a version and invalidates the cache immediately, so this is
    | only the upper bound for changes made outside the package.
    |
    */
    'cache_ttl_seconds' => (int) env('FORUMKIT_ENTITLEMENTS_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Gate abilities
    |--------------------------------------------------------------------------
    |
    | Maps a Laravel Gate ability to the entitlement that allows it. Packages whose
    | screens are gated by an ability are listed here with their own entitlement key;
    | point an ability at a different entitlement to use your own keys.
    |
    */
    'gates' => [
        'forumkit.settings.manage' => 'forumkit.settings.manage',
        'forumkit.entitlements.manage' => 'forumkit.entitlements.manage',
        'forumkit.entitlements.view' => 'forumkit.entitlements.view',
    ],

];
