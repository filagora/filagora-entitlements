<?php

declare(strict_types=1);

use Filagora\Core\Contracts\PrivacyContributor;
use Filagora\Core\Privacy\PrivacyContributorRegistry;
use Filagora\Entitlements\Models\EntitlementRequest;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Services\AuthorizationService;
use Filagora\Entitlements\Tests\Support\TestUser;

function contributorFor(): PrivacyContributor
{
    return app(PrivacyContributorRegistry::class)->all()['entitlements'];
}

it('exports a person\'s grants and requests, and no one else\'s', function () {
    $user = TestUser::make('Member');
    $other = TestUser::make('Other');
    UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'plugins.beta', 'grant_type' => 'manual', 'reason' => 'Tester']);
    UserEntitlementGrant::create(['user_id' => $other->id, 'entitlement_key' => 'secret.key', 'grant_type' => 'manual']);
    EntitlementRequest::create(['user_id' => $user->id, 'entitlement_key' => 'plugins.beta', 'status' => 'pending', 'request_reason' => 'Please', 'requested_at' => now()]);
    EntitlementRequest::create(['user_id' => $other->id, 'entitlement_key' => 'secret.key', 'status' => 'pending', 'request_reason' => 'Not mine', 'requested_at' => now()]);

    $export = contributorFor()->export($user);

    expect(array_keys($export))->toBe(['grants', 'requests'])
        ->and(array_column($export['grants'], 'entitlement_key'))->toBe(['plugins.beta'])
        ->and($export['grants'][0]['reason'])->toBe('Tester')
        ->and(array_column($export['requests'], 'request_reason'))->toBe(['Please']);
});

it('erases a person\'s grants and requests, and the access they gave goes at once', function () {
    $user = TestUser::make('Member');
    $other = TestUser::make('Other');
    UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'plugins.beta', 'grant_type' => 'manual']);
    UserEntitlementGrant::create(['user_id' => $other->id, 'entitlement_key' => 'plugins.beta', 'grant_type' => 'manual']);
    EntitlementRequest::create(['user_id' => $user->id, 'entitlement_key' => 'x', 'status' => 'pending', 'requested_at' => now()]);
    EntitlementRequest::create(['user_id' => $other->id, 'entitlement_key' => 'x', 'status' => 'pending', 'requested_at' => now()]);

    $access = app(AuthorizationService::class);
    expect($access->allows($user, 'plugins.beta'))->toBeTrue();

    contributorFor()->erase($user);

    expect(UserEntitlementGrant::where('user_id', $user->id)->count())->toBe(0)
        ->and(EntitlementRequest::where('user_id', $user->id)->count())->toBe(0)
        ->and(UserEntitlementGrant::where('user_id', $other->id)->count())->toBe(1)
        ->and(EntitlementRequest::where('user_id', $other->id)->count())->toBe(1)
        ->and($access->allows($user, 'plugins.beta'))->toBeFalse()
        ->and($access->allows($other, 'plugins.beta'))->toBeTrue();
});
