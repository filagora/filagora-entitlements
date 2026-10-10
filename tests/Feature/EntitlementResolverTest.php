<?php

declare(strict_types=1);

use ForumKit\Entitlements\Contracts\GrantSource;
use ForumKit\Entitlements\Contracts\SuperUserCheck;
use ForumKit\Entitlements\Enums\DefaultEntitlementTarget;
use ForumKit\Entitlements\Models\DefaultEntitlementGrant;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Registry\EntitlementRegistry;
use ForumKit\Entitlements\Services\EntitlementResolver;
use ForumKit\Entitlements\Tests\Support\TestUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

function resolver(): EntitlementResolver
{
    return app(EntitlementResolver::class);
}

function directGrant(TestUser $user, string $key, array $overrides = []): UserEntitlementGrant
{
    return UserEntitlementGrant::create(array_merge([
        'user_id' => $user->id,
        'entitlement_key' => $key,
        'grant_type' => 'manual',
        'starts_at' => now()->subHour(),
    ], $overrides));
}

it('grants an exact entitlement from a direct grant', function () {
    $user = TestUser::make();
    directGrant($user, 'forums.read');

    expect(resolver()->hasEntitlement($user, 'forums.read'))->toBeTrue()
        ->and(resolver()->hasEntitlement($user, 'forums.write'))->toBeFalse()
        ->and(resolver()->entitlementsFor($user))->toBe(['forums.read']);
});

it('matches wildcard grants against child entitlements only', function () {
    $user = TestUser::make();
    directGrant($user, 'api.plugins.*');

    expect(resolver()->hasEntitlement($user, 'api.plugins.read'))->toBeTrue()
        ->and(resolver()->hasEntitlement($user, 'api.plugins.read.deep'))->toBeTrue()
        ->and(resolver()->hasEntitlement($user, 'api.other.read'))->toBeFalse()
        ->and(resolver()->hasEntitlement($user, 'api.plugins'))->toBeFalse();
});

it('treats a lone .* as a literal, not a wildcard', function () {
    expect(resolver()->grantMatches('.*', 'anything'))->toBeFalse()
        ->and(resolver()->grantMatches('', 'x'))->toBeFalse()
        ->and(resolver()->grantMatches('x', ''))->toBeFalse();
});

it('partitions grants into exact keys and wildcard prefixes', function () {
    expect(resolver()->partitionGrants([' a.b ', 'c.*', 'a.b', '', 'c.*']))->toBe([
        'exact' => ['a.b'],
        'wildcard_prefixes' => ['c.'],
    ]);
});

it('ignores expired, revoked and not-yet-started direct grants', function () {
    $user = TestUser::make();
    directGrant($user, 'expired.key', ['expires_at' => now()->subMinute()]);
    directGrant($user, 'revoked.key', ['revoked_at' => now()->subMinute()]);
    directGrant($user, 'future.key', ['starts_at' => now()->addHour()]);
    directGrant($user, 'live.key', ['expires_at' => now()->addHour()]);

    expect(resolver()->entitlementsFor($user))->toBe(['live.key']);
});

it('applies default grants to authenticated users and only verified users where targeted', function () {
    DefaultEntitlementGrant::create(['entitlement_key' => 'everyone.key', 'target_type' => DefaultEntitlementTarget::AUTHENTICATED_USERS]);
    DefaultEntitlementGrant::create(['entitlement_key' => 'all.key', 'target_type' => DefaultEntitlementTarget::ALL_USERS]);
    DefaultEntitlementGrant::create(['entitlement_key' => 'verified.key', 'target_type' => DefaultEntitlementTarget::VERIFIED_USERS]);
    DefaultEntitlementGrant::create(['entitlement_key' => 'expired.key', 'target_type' => DefaultEntitlementTarget::ALL_USERS, 'expires_at' => now()->subMinute()]);

    $verified = TestUser::make('Verified', verified: true);
    $unverified = TestUser::make('Unverified', verified: false);

    expect(resolver()->entitlementsFor($verified))->toBe(['everyone.key', 'all.key', 'verified.key'])
        ->and(resolver()->entitlementsFor($unverified))->toBe(['everyone.key', 'all.key']);
});

it('explains where an entitlement came from', function () {
    $granter = TestUser::make('Granter');
    $user = TestUser::make('Member');
    DefaultEntitlementGrant::create(['entitlement_key' => 'forums.*', 'target_type' => DefaultEntitlementTarget::AUTHENTICATED_USERS, 'reason' => 'Everyone']);
    $grant = directGrant($user, 'forums.read', ['granted_by_id' => $granter->id, 'reason' => 'Trusted']);

    $explanation = resolver()->explain($user, 'forums.read');

    expect($explanation)->toHaveCount(2)
        ->and($explanation[0]['source_kind'])->toBe('default_grant')
        ->and($explanation[0]['source_summary'])->toBe('Applies to authenticated users')
        ->and($explanation[1]['source_kind'])->toBe('user_grant')
        ->and($explanation[1]['source_reference'])->toBe('Grant #'.$grant->id)
        ->and($explanation[1]['source_summary'])->toContain('Manual')->toContain('granted by Granter')
        ->and($explanation[1]['metadata']['granted_by_id'])->toBe($granter->id)
        ->and($explanation[1]['reason'])->toBe('Trusted')
        ->and(resolver()->explain($user, '  '))->toBe([])
        ->and(array_keys(resolver()->explanationsFor($user)))->toBe(['forums.*', 'forums.read']);
});

it('invalidates the cache when a user grant is written, revoked or deleted', function () {
    $user = TestUser::make();

    expect(resolver()->hasEntitlement($user, 'late.key'))->toBeFalse();

    $grant = directGrant($user, 'late.key');

    expect(resolver()->hasEntitlement($user, 'late.key'))->toBeTrue();

    $grant->update(['revoked_at' => now()]);

    expect(resolver()->hasEntitlement($user, 'late.key'))->toBeFalse();

    $grant->delete();
    directGrant($user, 'other.key');

    expect(resolver()->entitlementsFor($user))->toBe(['other.key']);
});

it('invalidates every user when a default grant changes', function () {
    $a = TestUser::make('A');
    $b = TestUser::make('B');

    expect(resolver()->entitlementsFor($a))->toBe([])->and(resolver()->entitlementsFor($b))->toBe([]);

    $default = DefaultEntitlementGrant::create(['entitlement_key' => 'new.default', 'target_type' => DefaultEntitlementTarget::ALL_USERS]);

    expect(resolver()->hasEntitlement($a, 'new.default'))->toBeTrue()
        ->and(resolver()->hasEntitlement($b, 'new.default'))->toBeTrue();

    $default->delete();

    expect(resolver()->hasEntitlement($a, 'new.default'))->toBeFalse();
});

it('resolves a user once per instance and then serves from memory', function () {
    $user = TestUser::make();
    directGrant($user, 'memo.key');

    resolver()->entitlementsFor($user);

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    resolver()->hasEntitlement($user, 'memo.key');
    resolver()->entitlementsFor($user);
    resolver()->grantsFor($user);

    expect($queries)->toBe(0);
});

it('lets a bound super-user check bypass every entitlement', function () {
    $user = TestUser::make();

    expect(resolver()->hasEntitlement($user, 'anything'))->toBeFalse();

    app()->bind(SuperUserCheck::class, fn () => new class implements SuperUserCheck
    {
        public function bypasses(Model $user): bool
        {
            return true;
        }
    });
    app()->forgetInstance(EntitlementResolver::class);

    expect(resolver()->hasEntitlement($user, 'anything'))->toBeTrue()
        ->and(resolver()->hasEntitlement($user, '  '))->toBeFalse();
});

it('merges grants contributed by other packages', function () {
    $user = TestUser::make();

    app(EntitlementRegistry::class)->grantSource(new class implements GrantSource
    {
        public function grantsFor(Model $user): array
        {
            return [[
                'entitlement_key' => 'membership.perk',
                'source_kind' => 'membership',
                'source_label' => 'Membership',
                'source_summary' => 'Pro plan',
                'source_reference' => 'Membership #1',
                'grant_type' => 'membership',
                'reason' => null,
                'starts_at' => null,
                'expires_at' => null,
                'metadata' => [],
            ]];
        }
    });
    app()->forgetInstance(EntitlementResolver::class);

    expect(resolver()->hasEntitlement($user, 'membership.perk'))->toBeTrue()
        ->and(resolver()->explain($user, 'membership.perk')[0]['source_kind'])->toBe('membership');
});
