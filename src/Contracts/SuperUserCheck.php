<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Lets a host grant every entitlement to certain users (for example a super-admin
 * role) without storing a grant per entitlement. Bind your own implementation in
 * the container; the default bypasses nobody.
 */
interface SuperUserCheck
{
    public function bypasses(Model $user): bool;
}
