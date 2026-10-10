<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Support;

use ForumKit\Entitlements\Contracts\SuperUserCheck;
use Illuminate\Database\Eloquent\Model;

final class NoSuperUser implements SuperUserCheck
{
    public function bypasses(Model $user): bool
    {
        return false;
    }
}
