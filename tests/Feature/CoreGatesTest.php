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
 * Core's shared abilities are answered from grants and declared as definitions.
 */
class CoreGatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shared_frontend_moderation_ability_follows_the_grant(): void
    {
        $user = TestUser::make('Moderator');

        $this->assertFalse(Gate::forUser($user)->allows(Abilities::MODERATE_FRONTEND));

        UserEntitlementGrant::create(['user_id' => $user->id, 'entitlement_key' => 'filagora.moderate_frontend', 'grant_type' => 'manual']);

        $this->assertTrue(Gate::forUser($user)->allows(Abilities::MODERATE_FRONTEND));
    }

    public function test_the_shared_ability_is_declared_as_a_definition(): void
    {
        $this->artisan('filagora:entitlements:sync')->assertExitCode(0);

        $this->assertTrue(EntitlementDefinition::where('key', 'filagora.moderate_frontend')->exists());
    }
}
