<?php

declare(strict_types=1);

namespace ForumKit\Entitlements;

use ForumKit\Core\Modules\Module;
use ForumKit\Core\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class EntitlementsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/forumkit-entitlements.php', 'forumkit-entitlements');
    }

    public function boot(): void
    {
        $this->app->make(ModuleRegistry::class)->register(
            new Module('entitlements', 'ForumKit Entitlements', 'zencart/forumkit-entitlements', requires: ['core']),
        );

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'forumkit-entitlements');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'forumkit-entitlements');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/forumkit-entitlements.php' => config_path('forumkit-entitlements.php'),
            ], 'forumkit-entitlements-config');
        }
    }
}
