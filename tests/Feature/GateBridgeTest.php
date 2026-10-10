<?php

declare(strict_types=1);

use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Tests\Support\TestUser;
use Illuminate\Support\Facades\Gate;

it('allows core settings access to users holding the mapped entitlement', function () {
    $user = TestUser::make();

    expect(Gate::forUser($user)->allows('forumkit.settings.manage'))->toBeFalse();

    UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'forumkit.settings.manage', 'grant_type' => 'manual']);

    expect(Gate::forUser($user)->allows('forumkit.settings.manage'))->toBeTrue();
});

it('denies a guest', function () {
    expect(Gate::allows('forumkit.settings.manage'))->toBeFalse();
});
