<?php

declare(strict_types=1);

use ForumKit\Entitlements\Contracts\EntitlementKeySource;
use ForumKit\Entitlements\Enums\DefaultEntitlementTarget;
use ForumKit\Entitlements\Models\DefaultEntitlementGrant;
use ForumKit\Entitlements\Models\EntitlementDefinition;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Registry\Definition;
use ForumKit\Entitlements\Registry\EntitlementRegistry;
use ForumKit\Entitlements\Services\EntitlementDefinitionCatalog;
use ForumKit\Entitlements\Tests\Support\TestUser;

function catalog(): EntitlementDefinitionCatalog
{
    return app(EntitlementDefinitionCatalog::class);
}

it('materialises normalised keys with a namespace', function () {
    $keys = catalog()->materialize([' forums.read ', 'forums.read', null, '', 'plain']);

    expect($keys)->toBe(['forums.read', 'plain']);

    $definition = EntitlementDefinition::where('key', 'forums.read')->sole();

    expect($definition->namespace)->toBe('forums')
        ->and($definition->label)->toBe('forums.read')
        ->and($definition->supports_wildcard_children)->toBeTrue()
        ->and($definition->is_grantable)->toBeTrue()
        ->and($definition->is_requestable)->toBeFalse()
        ->and(EntitlementDefinition::where('key', 'plain')->sole()->namespace)->toBeNull();
});

it('synchronises one key and ignores blanks', function () {
    expect(catalog()->synchronizeKey('  a.b '))->toBe('a.b')
        ->and(catalog()->synchronizeKey(null))->toBeNull()
        ->and(catalog()->synchronizeKey('   '))->toBeNull()
        ->and(EntitlementDefinition::count())->toBe(1);
});

it('creates definitions for registry declarations, key sources and keys already in use', function () {
    $registry = app(EntitlementRegistry::class);
    $registry->define(new Definition('pkg.declared', label: 'Declared', description: 'Does a thing', requestable: true, sortOrder: 7));
    $registry->keySource(new class implements EntitlementKeySource
    {
        public function keys(): array
        {
            return ['pkg.from.source'];
        }
    });

    $user = TestUser::make();
    UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'used.by.user', 'grant_type' => 'manual']);
    DefaultEntitlementGrant::create(['entitlement_key' => 'used.by.default', 'target_type' => DefaultEntitlementTarget::ALL_USERS]);

    $keys = catalog()->synchronizeAll();

    expect($keys)->toContain('pkg.declared', 'pkg.from.source', 'used.by.user', 'used.by.default');

    $declared = EntitlementDefinition::where('key', 'pkg.declared')->sole();

    expect($declared->label)->toBe('Declared')
        ->and($declared->description)->toBe('Does a thing')
        ->and($declared->is_requestable)->toBeTrue()
        ->and($declared->sort_order)->toBe(7);
});

it('keeps edits staff made to a declared definition', function () {
    app(EntitlementRegistry::class)->define(new Definition('pkg.edited', label: 'Original'));

    catalog()->synchronizeAll();
    EntitlementDefinition::where('key', 'pkg.edited')->update(['label' => 'Renamed by staff', 'is_active' => false]);
    catalog()->synchronizeAll();

    $definition = EntitlementDefinition::where('key', 'pkg.edited')->sole();

    expect($definition->label)->toBe('Renamed by staff')->and($definition->is_active)->toBeFalse();
});

it('creates a definition whenever a grant is saved for a new key', function () {
    $user = TestUser::make();

    UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'auto.user', 'grant_type' => 'manual']);
    DefaultEntitlementGrant::create(['entitlement_key' => 'auto.default', 'target_type' => DefaultEntitlementTarget::ALL_USERS]);

    expect(EntitlementDefinition::pluck('key')->sort()->values()->all())->toBe(['auto.default', 'auto.user']);
});

it('offers active definitions as selector options', function () {
    EntitlementDefinition::create(['key' => 'b.key', 'label' => 'B', 'sort_order' => 2]);
    EntitlementDefinition::create(['key' => 'a.key', 'label' => 'A', 'sort_order' => 1]);
    EntitlementDefinition::create(['key' => 'off.key', 'label' => 'Off', 'is_active' => false]);

    expect(EntitlementDefinition::selectorOptions())->toBe(['a.key' => 'a.key', 'b.key' => 'b.key']);
});

it('syncs from the artisan command', function () {
    app(EntitlementRegistry::class)->define(new Definition('cmd.key'));

    $this->artisan('forumkit:entitlements:sync')->expectsOutputToContain('in sync')->assertExitCode(0);

    expect(EntitlementDefinition::where('key', 'cmd.key')->exists())->toBeTrue();
});

it('declares the entitlement behind the settings screen', function () {
    catalog()->synchronizeAll();

    expect(EntitlementDefinition::where('key', 'forumkit.settings.manage')->sole()->supports_wildcard_children)->toBeFalse();
});
