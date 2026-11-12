<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Tests\Feature;

use Filagora\Core\Support\Abilities;
use Filagora\Entitlements\Models\EntitlementDefinition;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Tests\Support\TestUser;
use Filagora\Entitlements\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

/**
 * A host points core's shared ability at its own entitlement key in config, before boot.
 */
class CoreGatesRemapTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('filagora.gates', [
            Abilities::MODERATE_FRONTEND => 'operator.forum.moderate_frontend',
        ]);
    }

    public function test_the_ability_and_the_declared_definition_follow_the_remapped_key(): void
    {
        $user = TestUser::make('Moderator');

        UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'filagora.moderate_frontend', 'grant_type' => 'manual']);
        $this->assertFalse(Gate::forUser($user)->allows(Abilities::MODERATE_FRONTEND), 'the package key no longer opens the gate');

        UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'operator.forum.moderate_frontend', 'grant_type' => 'manual']);
        $this->assertTrue(Gate::forUser($user)->allows(Abilities::MODERATE_FRONTEND));

        UserEntitlementGrant::where('entitlement_key', 'filagora.moderate_frontend')->delete();

        $this->artisan('filagora:entitlements:sync')->assertExitCode(0);
        $this->assertTrue(EntitlementDefinition::where('key', 'operator.forum.moderate_frontend')->exists());
        $this->assertFalse(EntitlementDefinition::where('key', 'filagora.moderate_frontend')->exists());
    }
}
