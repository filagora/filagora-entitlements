<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Tests\Feature;

use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\EntitlementDefinitionResource;
use ForumKit\Entitlements\Models\EntitlementDefinition;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Tests\Support\TestUser;
use ForumKit\Entitlements\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

/**
 * A host points a gate ability at its own entitlement key in config, before boot.
 */
class GateRemapTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('forumkit-entitlements.gates', [
            'forumkit.entitlements.manage' => 'operator.entitlements.manage',
        ]);
    }

    public function test_the_ability_follows_the_remapped_entitlement_key(): void
    {
        $user = TestUser::make('Staff');
        $this->actingAs($user);

        $this->assertFalse(EntitlementDefinitionResource::canViewAny());

        UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'forumkit.entitlements.manage', 'grant_type' => 'manual']);
        $this->assertFalse(EntitlementDefinitionResource::canViewAny(), 'the package key no longer opens the gate');

        UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'operator.entitlements.manage', 'grant_type' => 'manual']);
        $this->assertTrue(EntitlementDefinitionResource::canViewAny());
        $this->assertTrue(Gate::forUser($user)->allows('forumkit.entitlements.manage'));
    }

    public function test_the_remapped_key_is_declared_as_a_definition(): void
    {
        $this->artisan('forumkit:entitlements:sync')->assertExitCode(0);

        $this->assertTrue(EntitlementDefinition::where('key', 'operator.entitlements.manage')->exists());
        $this->assertFalse(EntitlementDefinition::where('key', 'forumkit.entitlements.manage')->exists());
    }
}
