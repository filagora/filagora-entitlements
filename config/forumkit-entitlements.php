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

];
