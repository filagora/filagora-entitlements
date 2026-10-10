<?php

declare(strict_types=1);

use ForumKit\Entitlements\Concerns\AuthorizesWithEntitlements;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Services\AuthorizationService;
use ForumKit\Entitlements\Tests\Support\TestUser;
use Illuminate\Foundation\Auth\User as PlainUser;

function authz(): AuthorizationService
{
    return app(AuthorizationService::class);
}

function give(TestUser $user, string ...$keys): void
{
    foreach ($keys as $key) {
        UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => $key, 'grant_type' => 'manual']);
    }
}

it('denies guests and users that are not the host user model', function () {
    expect(authz()->allows(null, 'x'))->toBeFalse()
        ->and(authz()->allowsAny(null, ['x']))->toBeFalse()
        ->and(authz()->allowsAll(null, ['x']))->toBeFalse()
        ->and(authz()->allows(new PlainUser, 'x'))->toBeFalse();
});

it('allows, allowsAny and allowsAll follow the user entitlements', function () {
    $user = TestUser::make();
    give($user, 'a.one', 'b.*');

    expect(authz()->allows($user, 'a.one'))->toBeTrue()
        ->and(authz()->allows($user, 'a.two'))->toBeFalse()
        ->and(authz()->allowsAny($user, ['a.two', 'b.child']))->toBeTrue()
        ->and(authz()->allowsAny($user, ['a.two', 'c.x']))->toBeFalse()
        ->and(authz()->allowsAll($user, ['a.one', 'b.child']))->toBeTrue()
        ->and(authz()->allowsAll($user, ['a.one', 'c.x']))->toBeFalse()
        ->and(authz()->allowsAny($user, []))->toBeFalse()
        ->and(authz()->allowsAll($user, []))->toBeTrue();
});

it('gives policies the same answers through the trait', function () {
    $user = TestUser::make();
    give($user, 'policy.key');

    $policy = new class
    {
        use AuthorizesWithEntitlements;

        public function check(TestUser $user): array
        {
            return [$this->allows($user, 'policy.key'), $this->allowsAny($user, ['nope', 'policy.key']), $this->allowsAll($user, ['policy.key', 'nope'])];
        }
    };

    expect($policy->check($user))->toBe([true, true, false]);
});

it('exposes the host-user relations and a shortcut through HasEntitlements', function () {
    $user = TestUser::make();
    give($user, 'trait.key');

    expect($user->entitlementGrants)->toHaveCount(1)
        ->and($user->entitlementRequests)->toHaveCount(0)
        ->and($user->reviewedEntitlementRequests)->toHaveCount(0)
        ->and($user->hasEntitlement('trait.key'))->toBeTrue();
});
