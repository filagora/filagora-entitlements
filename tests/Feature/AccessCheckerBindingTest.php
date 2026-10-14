<?php

declare(strict_types=1);

use Filagora\Core\Contracts\AccessChecker;
use Filagora\Core\Filament\Concerns\GatedByAbility as CoreGatedByAbility;
use Filagora\Entitlements\Filament\Concerns\GatedByAbility as DeprecatedGatedByAbility;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Services\AuthorizationService;
use Filagora\Entitlements\Support\Abilities;
use Filagora\Entitlements\Tests\Support\TestUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

it('is the access checker the other packages ask, in place of core\'s deny-everyone default', function () {
    expect(app(AccessChecker::class))->toBeInstanceOf(AuthorizationService::class);

    $holder = TestUser::make('Holder');
    UserEntitlementGrant::create(['user_id' => $holder->id, 'entitlement_key' => 'plugins.beta', 'grant_type' => 'manual']);

    expect(app(AccessChecker::class)->allows($holder, 'plugins.beta'))->toBeTrue()
        ->and(app(AccessChecker::class)->allows(TestUser::make('Other'), 'plugins.beta'))->toBeFalse()
        ->and(app(AccessChecker::class)->allows(null, 'plugins.beta'))->toBeFalse()
        ->and(app(AccessChecker::class)->allowsAny($holder, ['x', 'plugins.beta']))->toBeTrue()
        ->and(app(AccessChecker::class)->allowsAll($holder, ['x', 'plugins.beta']))->toBeFalse();
});

it('lets a host bind its own checker, which this package\'s own gates then follow', function () {
    $user = TestUser::make('Staff');
    expect(Gate::forUser($user)->allows(Abilities::MANAGE))->toBeFalse();

    app()->bind(AccessChecker::class, fn () => new class implements AccessChecker
    {
        public function allows(?Authenticatable $user, string $key): bool
        {
            return true;
        }

        public function allowsAny(?Authenticatable $user, array $keys): bool
        {
            return true;
        }

        public function allowsAll(?Authenticatable $user, array $keys): bool
        {
            return true;
        }
    });

    expect(Gate::forUser($user)->allows(Abilities::MANAGE))->toBeTrue();
});

it('keeps the old import of the resource trait working, as an alias of core\'s', function () {
    $resource = new class
    {
        use DeprecatedGatedByAbility;

        protected static function abilityFor(string|UnitEnum $action, ?Model $record): ?string
        {
            return Abilities::MANAGE;
        }
    };

    expect(in_array(CoreGatedByAbility::class, class_uses(DeprecatedGatedByAbility::class), true))->toBeTrue()
        ->and(method_exists($resource, 'getAuthorizationResponse'))->toBeTrue();
});
