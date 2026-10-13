<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Support;

use Filagora\Entitlements\Contracts\SuperUserCheck;
use Illuminate\Database\Eloquent\Model;

final class NoSuperUser implements SuperUserCheck
{
    public function bypasses(Model $user): bool
    {
        return false;
    }
}
