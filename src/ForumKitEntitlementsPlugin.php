<?php

declare(strict_types=1);

namespace ForumKit\Entitlements;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\DefaultEntitlementGrantResource;
use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\EntitlementDefinitionResource;
use ForumKit\Entitlements\Filament\Resources\EntitlementRequests\EntitlementRequestResource;
use ForumKit\Entitlements\Filament\Resources\UserEntitlementGrants\UserEntitlementGrantResource;

class ForumKitEntitlementsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'forumkit-entitlements';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            EntitlementDefinitionResource::class,
            UserEntitlementGrantResource::class,
            DefaultEntitlementGrantResource::class,
            EntitlementRequestResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
