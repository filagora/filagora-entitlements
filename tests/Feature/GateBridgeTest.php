<?php

declare(strict_types=1);

use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Tests\Support\TestUser;
use Illuminate\Support\Facades\Gate;

it('allows core settings access to users holding the mapped entitlement', function () {
    $user = TestUser::make();

    expect(Gate::forUser($user)->allows('filagora.settings.manage'))->toBeFalse();

    UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'filagora.settings.manage', 'grant_type' => 'manual']);

    expect(Gate::forUser($user)->allows('filagora.settings.manage'))->toBeTrue();
});

it('denies a guest', function () {
    expect(Gate::allows('filagora.settings.manage'))->toBeFalse();
});
