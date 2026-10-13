<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filagora\Entitlements\Enums\DefaultEntitlementTarget;
use Filagora\Entitlements\Enums\EntitlementRequestStatus;
use Filagora\Entitlements\Filament\Concerns\GatedByAbility;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\DefaultEntitlementGrantResource;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages\CreateDefaultEntitlementGrant;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages\EditDefaultEntitlementGrant;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages\ListDefaultEntitlementGrants;
use Filagora\Entitlements\Filament\Resources\EntitlementDefinitions\EntitlementDefinitionResource;
use Filagora\Entitlements\Filament\Resources\EntitlementDefinitions\Pages\CreateEntitlementDefinition;
use Filagora\Entitlements\Filament\Resources\EntitlementDefinitions\Pages\EditEntitlementDefinition;
use Filagora\Entitlements\Filament\Resources\EntitlementDefinitions\Pages\ListEntitlementDefinitions;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\EntitlementRequestResource;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\Pages\ListEntitlementRequests;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\Pages\ViewEntitlementRequest;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Pages\CreateUserEntitlementGrant;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Pages\EditUserEntitlementGrant;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Pages\ListUserEntitlementGrants;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\UserEntitlementGrantResource;
use Filagora\Entitlements\Models\DefaultEntitlementGrant;
use Filagora\Entitlements\Models\EntitlementDefinition;
use Filagora\Entitlements\Models\EntitlementRequest;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Services\EntitlementResolver;
use Filagora\Entitlements\Support\Abilities;
use Filagora\Entitlements\Tests\Support\TestUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

/**
 * Sign in a user who holds the given entitlements (through real grants, so the whole
 * Gate -> entitlement -> resolver chain is exercised).
 */
function staff(string ...$entitlements): TestUser
{
    $user = TestUser::make('Staff');

    foreach ($entitlements as $key) {
        UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => $key, 'grant_type' => 'manual']);
    }

    actingAs($user);

    return $user;
}

function manager(): TestUser
{
    return staff(Abilities::MANAGE, Abilities::VIEW);
}

// ---------------------------------------------------------------- authorization

it('denies every screen to a user holding no entitlement', function () {
    staff();

    foreach ([EntitlementDefinitionResource::class, UserEntitlementGrantResource::class, DefaultEntitlementGrantResource::class, EntitlementRequestResource::class] as $resource) {
        expect($resource::canViewAny())->toBeFalse("{$resource} viewAny");
    }

    expect(EntitlementDefinitionResource::canCreate())->toBeFalse();
});

it('denies an action whose gate ability is undefined or has none', function () {
    $user = staff(Abilities::MANAGE, Abilities::VIEW);

    $resource = new class
    {
        use GatedByAbility;

        public static ?string $ability = null;

        protected static function abilityFor(string|UnitEnum $action, ?Model $record): ?string
        {
            return self::$ability;
        }
    };

    $resource::$ability = 'filagora.nothing.defines.this';
    expect($resource::getAuthorizationResponse('viewAny')->allowed())->toBeFalse();

    $resource::$ability = null;
    expect($resource::getAuthorizationResponse('viewAny')->allowed())->toBeFalse();

    $resource::$ability = Abilities::MANAGE;
    expect($resource::getAuthorizationResponse('viewAny')->allowed())->toBeTrue();
});

it('lets the manage entitlement into definitions, user grants and default grants but not requests', function () {
    staff(Abilities::MANAGE);

    expect(EntitlementDefinitionResource::canViewAny())->toBeTrue()
        ->and(EntitlementDefinitionResource::canCreate())->toBeTrue()
        ->and(UserEntitlementGrantResource::canViewAny())->toBeTrue()
        ->and(DefaultEntitlementGrantResource::canViewAny())->toBeTrue()
        ->and(EntitlementRequestResource::canViewAny())->toBeFalse();
});

it('lets the view entitlement review requests but not manage definitions or grants', function () {
    staff(Abilities::VIEW);

    expect(EntitlementRequestResource::canViewAny())->toBeTrue()
        ->and(EntitlementDefinitionResource::canViewAny())->toBeFalse()
        ->and(UserEntitlementGrantResource::canViewAny())->toBeFalse()
        ->and(DefaultEntitlementGrantResource::canViewAny())->toBeFalse();
});

it('never allows requests to be created, edited or deleted from the dashboard', function () {
    $user = manager();
    $request = EntitlementRequest::create(['user_id' => $user->id, 'entitlement_key' => 'k', 'status' => EntitlementRequestStatus::PENDING]);

    expect(EntitlementRequestResource::canCreate())->toBeFalse()
        ->and(EntitlementRequestResource::canEdit($request))->toBeFalse()
        ->and(EntitlementRequestResource::canDelete($request))->toBeFalse()
        ->and(EntitlementRequestResource::canDeleteAny())->toBeFalse();
});

// ----------------------------------------------------------------- definitions

it('lists, creates and edits entitlement definitions', function () {
    manager();
    $existing = EntitlementDefinition::create(['key' => 'forums.read', 'label' => 'Read forums']);

    Livewire::test(ListEntitlementDefinitions::class)->assertCanSeeTableRecords([$existing]);

    Livewire::test(CreateEntitlementDefinition::class)
        ->fillForm(['key' => 'forums.write', 'label' => 'Write', 'sort_order' => 3, 'is_requestable' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = EntitlementDefinition::where('key', 'forums.write')->sole();

    expect($created->is_requestable)->toBeTrue()->and($created->sort_order)->toBe(3);

    Livewire::test(EditEntitlementDefinition::class, ['record' => $created->getKey()])
        ->fillForm(['label' => 'Write to forums'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($created->refresh()->label)->toBe('Write to forums');
});

it('requires a unique definition key', function () {
    manager();
    EntitlementDefinition::create(['key' => 'dup.key', 'label' => 'Dup']);

    Livewire::test(CreateEntitlementDefinition::class)
        ->fillForm(['key' => 'dup.key', 'label' => 'Again'])
        ->call('create')
        ->assertHasFormErrors(['key' => 'unique']);
});

// --------------------------------------------------------------- default grants

it('lists, creates and edits default grants, and the change reaches users at once', function () {
    manager();
    EntitlementDefinition::create(['key' => 'perk.default', 'label' => 'Perk']);
    $other = TestUser::make('Other');

    expect(app(EntitlementResolver::class)->hasEntitlement($other, 'perk.default'))->toBeFalse();

    Livewire::test(CreateDefaultEntitlementGrant::class)
        ->fillForm(['entitlement_key' => 'perk.default', 'target_type' => DefaultEntitlementTarget::ALL_USERS->value])
        ->call('create')
        ->assertHasNoFormErrors();

    $grant = DefaultEntitlementGrant::sole();

    Livewire::test(ListDefaultEntitlementGrants::class)->assertCanSeeTableRecords([$grant]);

    expect(app(EntitlementResolver::class)->hasEntitlement($other, 'perk.default'))->toBeTrue();

    Livewire::test(EditDefaultEntitlementGrant::class, ['record' => $grant->getKey()])
        ->fillForm(['target_type' => DefaultEntitlementTarget::VERIFIED_USERS->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($grant->refresh()->target_type)->toBe(DefaultEntitlementTarget::VERIFIED_USERS);
});

it('only offers known entitlement definitions when creating a default grant', function () {
    manager();

    Livewire::test(CreateDefaultEntitlementGrant::class)
        ->fillForm(['entitlement_key' => 'not.a.definition', 'target_type' => DefaultEntitlementTarget::ALL_USERS->value])
        ->call('create')
        ->assertHasFormErrors(['entitlement_key']);
});

// ----------------------------------------------------------------- user grants

it('lists, creates, edits and revokes user grants, with access following', function () {
    $admin = manager();
    EntitlementDefinition::create(['key' => 'perk.user', 'label' => 'Perk']);
    $member = TestUser::make('Member');
    $resolver = fn () => app(EntitlementResolver::class);

    Livewire::test(CreateUserEntitlementGrant::class)
        ->fillForm(['user_id' => $member->id, 'entitlement_key' => 'perk.user', 'grant_type' => 'manual'])
        ->call('create')
        ->assertHasNoFormErrors();

    $grant = UserEntitlementGrant::where('user_id', $member->id)->sole();

    expect($grant->granted_by_id)->toBe($admin->id)
        ->and($resolver()->hasEntitlement($member, 'perk.user'))->toBeTrue();

    Livewire::test(ListUserEntitlementGrants::class)->assertCanSeeTableRecords([$grant]);

    Livewire::test(EditUserEntitlementGrant::class, ['record' => $grant->getKey()])
        ->fillForm(['revoked_at' => now(), 'revoked_by_id' => $admin->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($grant->refresh()->revoked_at)->not->toBeNull()
        ->and($resolver()->hasEntitlement($member, 'perk.user'))->toBeFalse();
});

// -------------------------------------------------------------------- requests

function pending(TestUser $user, string $key = 'perk.requested'): EntitlementRequest
{
    return EntitlementRequest::create([
        'user_id' => $user->id,
        'entitlement_key' => $key,
        'status' => EntitlementRequestStatus::PENDING,
        'request_reason' => 'Please',
        'requested_at' => now(),
    ]);
}

it('lists requests and filters by status', function () {
    staff(Abilities::VIEW);
    $member = TestUser::make('Member');
    $pending = pending($member);
    $approved = EntitlementRequest::create(['user_id' => $member->id, 'entitlement_key' => 'k2', 'status' => EntitlementRequestStatus::APPROVED]);

    Livewire::test(ListEntitlementRequests::class)
        ->assertCanSeeTableRecords([$pending, $approved])
        ->filterTable('status', EntitlementRequestStatus::PENDING->value)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$approved]);
});

it('approves a request from the view page and grants access', function () {
    $reviewer = staff(Abilities::VIEW);
    $member = TestUser::make('Member');
    $request = pending($member);

    Livewire::test(ViewEntitlementRequest::class, ['record' => $request->getKey()])
        ->assertActionVisible('approve')
        ->assertActionVisible('reject')
        ->assertActionHidden('revoke')
        ->callAction('approve', ['review_notes' => 'Fine'])
        ->assertNotified();

    $request->refresh();

    expect($request->status)->toBe(EntitlementRequestStatus::APPROVED)
        ->and($request->reviewed_by_id)->toBe($reviewer->id)
        ->and(app(EntitlementResolver::class)->hasEntitlement($member, 'perk.requested'))->toBeTrue();
});

it('rejects a request only with review notes', function () {
    staff(Abilities::VIEW);
    $request = pending(TestUser::make('Member'));

    Livewire::test(ViewEntitlementRequest::class, ['record' => $request->getKey()])
        ->callAction('reject', ['review_notes' => ''])
        ->assertHasActionErrors(['review_notes' => 'required']);

    expect($request->refresh()->status)->toBe(EntitlementRequestStatus::PENDING);

    Livewire::test(ViewEntitlementRequest::class, ['record' => $request->getKey()])
        ->callAction('reject', ['review_notes' => 'Not now'])
        ->assertHasNoActionErrors();

    expect($request->refresh()->status)->toBe(EntitlementRequestStatus::REJECTED);
});

it('revokes an approved request and removes access', function () {
    staff(Abilities::VIEW);
    $member = TestUser::make('Member');
    $request = pending($member);

    Livewire::test(ViewEntitlementRequest::class, ['record' => $request->getKey()])->callAction('approve', []);

    expect(app(EntitlementResolver::class)->hasEntitlement($member, 'perk.requested'))->toBeTrue();

    Livewire::test(ViewEntitlementRequest::class, ['record' => $request->getKey()])
        ->assertActionHidden('approve')
        ->assertActionVisible('revoke')
        ->callAction('revoke', ['review_notes' => 'Misused']);

    expect($request->refresh()->status)->toBe(EntitlementRequestStatus::REVOKED)
        ->and(app(EntitlementResolver::class)->hasEntitlement($member, 'perk.requested'))->toBeFalse();
});

it('keeps the request screens closed to users without the view entitlement', function () {
    staff(Abilities::MANAGE);
    $request = pending(TestUser::make('Member'));

    Livewire::test(ViewEntitlementRequest::class, ['record' => $request->getKey()])->assertForbidden();
});

it('shows the pending count as a navigation badge, counted once per request', function () {
    staff(Abilities::VIEW);
    $member = TestUser::make('Member');
    pending($member);
    pending($member, 'k2');
    EntitlementRequest::create(['user_id' => $member->id, 'entitlement_key' => 'k3', 'status' => EntitlementRequestStatus::APPROVED]);

    expect(EntitlementRequestResource::getPendingRequestCount())->toBe(2)
        ->and(EntitlementRequestResource::getNavigationBadge())->toBe('2');
});

it('places the screens in the members and queues navigation groups', function () {
    expect(EntitlementDefinitionResource::getNavigationGroup())->toBe('Members')
        ->and(UserEntitlementGrantResource::getNavigationGroup())->toBe('Members')
        ->and(DefaultEntitlementGrantResource::getNavigationGroup())->toBe('Members')
        ->and(EntitlementRequestResource::getNavigationGroup())->toBe('Queues');
});
