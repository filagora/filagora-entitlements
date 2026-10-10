<?php

declare(strict_types=1);

use ForumKit\Entitlements\Actions\ApproveEntitlementRequest;
use ForumKit\Entitlements\Actions\RejectEntitlementRequest;
use ForumKit\Entitlements\Actions\RevokeEntitlementRequest;
use ForumKit\Entitlements\Enums\EntitlementRequestStatus;
use ForumKit\Entitlements\Events\EntitlementRequestApproved;
use ForumKit\Entitlements\Events\EntitlementRequestRejected;
use ForumKit\Entitlements\Events\EntitlementRequestRevoked;
use ForumKit\Entitlements\Models\EntitlementRequest;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Services\EntitlementResolver;
use ForumKit\Entitlements\Tests\Support\TestUser;
use Illuminate\Support\Facades\Event;

function pendingRequest(TestUser $user, string $key = 'perk.key'): EntitlementRequest
{
    return EntitlementRequest::create([
        'user_id' => $user->id,
        'entitlement_key' => $key,
        'status' => EntitlementRequestStatus::PENDING,
        'request_reason' => 'Please',
        'requested_at' => now(),
    ]);
}

it('approves a request by creating the grant it asks for', function () {
    Event::fake([EntitlementRequestApproved::class]);
    $user = TestUser::make('Requester');
    $reviewer = TestUser::make('Reviewer');
    $request = pendingRequest($user);

    $approved = app(ApproveEntitlementRequest::class)($request, $reviewer, '  Looks fine  ', now()->addMonth());

    $grant = UserEntitlementGrant::sole();

    expect($approved->status)->toBe(EntitlementRequestStatus::APPROVED)
        ->and($approved->reviewed_by_id)->toBe($reviewer->id)
        ->and($approved->review_notes)->toBe('Looks fine')
        ->and($grant->user_id)->toBe($user->id)
        ->and($grant->grant_type)->toBe('approved_request')
        ->and($grant->source_type)->toBe(EntitlementRequest::class)
        ->and($grant->source_id)->toBe($request->id)
        ->and($grant->granted_by_id)->toBe($reviewer->id)
        ->and($grant->reason)->toBe('Looks fine')
        ->and(app(EntitlementResolver::class)->hasEntitlement($user, 'perk.key'))->toBeTrue();

    Event::assertDispatched(EntitlementRequestApproved::class, fn ($event) => $event->request->is($approved));
});

it('approving twice reuses the same grant', function () {
    $user = TestUser::make();
    $reviewer = TestUser::make('Reviewer');
    $request = pendingRequest($user);

    app(ApproveEntitlementRequest::class)($request, $reviewer);
    app(ApproveEntitlementRequest::class)($request, $reviewer, 'again');

    expect(UserEntitlementGrant::count())->toBe(1);
});

it('rejects a request without granting anything', function () {
    Event::fake([EntitlementRequestRejected::class]);
    $user = TestUser::make();
    $reviewer = TestUser::make('Reviewer');

    $rejected = app(RejectEntitlementRequest::class)(pendingRequest($user), $reviewer, 'No');

    expect($rejected->status)->toBe(EntitlementRequestStatus::REJECTED)
        ->and($rejected->review_notes)->toBe('No')
        ->and($rejected->reviewed_by_id)->toBe($reviewer->id)
        ->and(UserEntitlementGrant::count())->toBe(0);

    Event::assertDispatched(EntitlementRequestRejected::class);
});

it('revoking a request revokes the grants it created and removes the access', function () {
    Event::fake([EntitlementRequestRevoked::class]);
    $user = TestUser::make();
    $reviewer = TestUser::make('Reviewer');
    $request = app(ApproveEntitlementRequest::class)(pendingRequest($user), $reviewer);

    expect(app(EntitlementResolver::class)->hasEntitlement($user, 'perk.key'))->toBeTrue();

    $revoked = app(RevokeEntitlementRequest::class)($request, $reviewer, 'Misused');

    $grant = UserEntitlementGrant::sole();

    expect($revoked->status)->toBe(EntitlementRequestStatus::REVOKED)
        ->and($grant->revoked_at)->not->toBeNull()
        ->and($grant->revoked_by_id)->toBe($reviewer->id)
        ->and($grant->reason)->toBe('Misused')
        ->and(app(EntitlementResolver::class)->hasEntitlement($user, 'perk.key'))->toBeFalse();

    Event::assertDispatched(EntitlementRequestRevoked::class);
});

it('exposes the request relations', function () {
    $user = TestUser::make();
    $reviewer = TestUser::make('Reviewer');
    $request = app(ApproveEntitlementRequest::class)(pendingRequest($user), $reviewer);

    expect($request->user->is($user))->toBeTrue()
        ->and($request->reviewedBy->is($reviewer))->toBeTrue()
        ->and($request->grants)->toHaveCount(1)
        ->and($request->grants->first()->source->is($request))->toBeTrue()
        ->and($request->grants->first()->grantedBy->is($reviewer))->toBeTrue();
});
