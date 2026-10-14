<?php

declare(strict_types=1);

namespace Filagora\Entitlements;

use Filagora\Core\Contracts\AccessChecker;
use Filagora\Core\Modules\Module;
use Filagora\Core\Modules\ModuleRegistry;
use Filagora\Core\Navigation\NavigationGroupDefinition;
use Filagora\Core\Navigation\NavigationGroupRegistry;
use Filagora\Core\Schema\PackageSchema;
use Filagora\Core\Schema\SchemaRegistry;
use Filagora\Core\Support\GateAbilities;
use Filagora\Entitlements\Commands\SyncEntitlementsCommand;
use Filagora\Entitlements\Contracts\SuperUserCheck;
use Filagora\Entitlements\Models\DefaultEntitlementGrant;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Observers\BumpDefaultEntitlementsCacheObserver;
use Filagora\Entitlements\Observers\BumpUserEntitlementsCacheObserver;
use Filagora\Entitlements\Observers\SyncEntitlementDefinitionObserver;
use Filagora\Entitlements\Registry\Definition;
use Filagora\Entitlements\Registry\EntitlementRegistry;
use Filagora\Entitlements\Services\AuthorizationService;
use Filagora\Entitlements\Services\EntitlementResolver;
use Filagora\Entitlements\Support\EntitlementCache;
use Filagora\Entitlements\Support\NoSuperUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class EntitlementsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filagora-entitlements.php', 'filagora-entitlements');

        $this->app->singleton(EntitlementRegistry::class);
        $this->app->singleton(EntitlementCache::class);
        $this->app->bindIf(SuperUserCheck::class, NoSuperUser::class);

        // A plain bind, not bindIf: core binds a deny-everyone default that this must replace. A host that
        // wants its own checker binds it in its own provider, which runs after the packages'.
        $this->app->bind(AccessChecker::class, AuthorizationService::class);
        $this->app->scoped(EntitlementResolver::class);
    }

    public function boot(): void
    {
        $this->app->make(ModuleRegistry::class)->register(
            new Module('entitlements', 'Filagora Entitlements', 'filagora/filagora-entitlements', requires: ['core']),
        );

        $this->app->make(SchemaRegistry::class)->register(
            PackageSchema::fromFile('entitlements', __DIR__.'/../database/schema.php', __DIR__.'/../database/migrations'),
        );

        $this->registerNavigationGroups();
        $this->declareOwnEntitlements();

        DefaultEntitlementGrant::observe(BumpDefaultEntitlementsCacheObserver::class);
        DefaultEntitlementGrant::observe(SyncEntitlementDefinitionObserver::class);
        UserEntitlementGrant::observe(BumpUserEntitlementsCacheObserver::class);
        UserEntitlementGrant::observe(SyncEntitlementDefinitionObserver::class);

        $this->defineGates();

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filagora-entitlements');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filagora-entitlements');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([SyncEntitlementsCommand::class]);

            $this->publishes([
                __DIR__.'/../config/filagora-entitlements.php' => config_path('filagora-entitlements.php'),
            ], 'filagora-entitlements-config');
        }
    }

    private function registerNavigationGroups(): void
    {
        $groups = $this->app->make(NavigationGroupRegistry::class);

        $groups->register(new NavigationGroupDefinition(
            key: 'members',
            label: fn (): string => __('filagora-entitlements::messages.navigation_groups.members'),
            icon: 'heroicon-o-user-group',
            sort: 20,
        ));

        $groups->register(new NavigationGroupDefinition(
            key: 'queues',
            label: fn (): string => __('filagora-entitlements::messages.navigation_groups.queues'),
            icon: 'heroicon-o-inbox-stack',
            sort: 30,
        ));
    }

    /**
     * The entitlements this package (and core, whose screens it gates) owns.
     */
    private function declareOwnEntitlements(): void
    {
        $registry = $this->app->make(EntitlementRegistry::class);

        foreach (config('filagora-entitlements.gates', []) as $entitlement) {
            if (is_string($entitlement) && $entitlement !== '') {
                $registry->define(new Definition($entitlement, wildcardChildren: false));
            }
        }
    }

    /**
     * Expose entitlements to Laravel's Gate: each configured ability is allowed when the
     * user holds the mapped entitlement. Hosts remap abilities to their own keys in config.
     */
    private function defineGates(): void
    {
        GateAbilities::define('filagora-entitlements.gates');
    }
}
