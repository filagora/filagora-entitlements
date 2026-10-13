<?php

declare(strict_types=1);

namespace Filagora\Entitlements;

use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\DefaultEntitlementGrantResource;
use Filagora\Entitlements\Filament\Resources\EntitlementDefinitions\EntitlementDefinitionResource;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\EntitlementRequestResource;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\UserEntitlementGrantResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilagoraEntitlementsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filagora-entitlements';
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
