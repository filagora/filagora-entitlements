<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Tests\Support;

use Filament\Panel;
use Filament\PanelProvider;
use ForumKit\Core\ForumKitCorePlugin;
use ForumKit\Entitlements\ForumKitEntitlementsPlugin;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(ForumKitCorePlugin::make())
            ->plugin(ForumKitEntitlementsPlugin::make());
    }
}
