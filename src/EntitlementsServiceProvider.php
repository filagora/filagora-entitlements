<?php

declare(strict_types=1);

namespace ForumKit\Entitlements;

use ForumKit\Core\Modules\Module;
use ForumKit\Core\Modules\ModuleRegistry;
use ForumKit\Core\Navigation\NavigationGroupDefinition;
use ForumKit\Core\Navigation\NavigationGroupRegistry;
use ForumKit\Entitlements\Commands\SyncEntitlementsCommand;
use ForumKit\Entitlements\Contracts\SuperUserCheck;
use ForumKit\Entitlements\Models\DefaultEntitlementGrant;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Observers\BumpDefaultEntitlementsCacheObserver;
use ForumKit\Entitlements\Observers\BumpUserEntitlementsCacheObserver;
use ForumKit\Entitlements\Observers\SyncEntitlementDefinitionObserver;
use ForumKit\Entitlements\Registry\Definition;
use ForumKit\Entitlements\Registry\EntitlementRegistry;
use ForumKit\Entitlements\Services\AuthorizationService;
use ForumKit\Entitlements\Services\EntitlementResolver;
use ForumKit\Entitlements\Support\EntitlementCache;
use ForumKit\Entitlements\Support\NoSuperUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class EntitlementsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/forumkit-entitlements.php', 'forumkit-entitlements');

        $this->app->singleton(EntitlementRegistry::class);
        $this->app->singleton(EntitlementCache::class);
        $this->app->bindIf(SuperUserCheck::class, NoSuperUser::class);
        $this->app->scoped(EntitlementResolver::class);
    }

    public function boot(): void
    {
        $this->app->make(ModuleRegistry::class)->register(
            new Module('entitlements', 'ForumKit Entitlements', 'zencart/forumkit-entitlements', requires: ['core']),
        );

        $this->registerNavigationGroups();
        $this->declareOwnEntitlements();

        DefaultEntitlementGrant::observe(BumpDefaultEntitlementsCacheObserver::class);
        DefaultEntitlementGrant::observe(SyncEntitlementDefinitionObserver::class);
        UserEntitlementGrant::observe(BumpUserEntitlementsCacheObserver::class);
        UserEntitlementGrant::observe(SyncEntitlementDefinitionObserver::class);

        $this->defineGates();

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'forumkit-entitlements');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'forumkit-entitlements');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([SyncEntitlementsCommand::class]);

            $this->publishes([
                __DIR__.'/../config/forumkit-entitlements.php' => config_path('forumkit-entitlements.php'),
            ], 'forumkit-entitlements-config');
        }
    }

    private function registerNavigationGroups(): void
    {
        $groups = $this->app->make(NavigationGroupRegistry::class);

        $groups->register(new NavigationGroupDefinition(
            key: 'members',
            label: fn (): string => __('forumkit-entitlements::messages.navigation_groups.members'),
            icon: 'heroicon-o-user-group',
            sort: 20,
        ));

        $groups->register(new NavigationGroupDefinition(
            key: 'queues',
            label: fn (): string => __('forumkit-entitlements::messages.navigation_groups.queues'),
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

        foreach (config('forumkit-entitlements.gates', []) as $entitlement) {
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
        foreach (config('forumkit-entitlements.gates', []) as $ability => $entitlement) {
            if (! is_string($ability) || ! is_string($entitlement)) {
                continue;
            }

            Gate::define($ability, fn (?Authenticatable $user): bool => $this->app->make(AuthorizationService::class)->allows($user, $entitlement));
        }
    }
}
