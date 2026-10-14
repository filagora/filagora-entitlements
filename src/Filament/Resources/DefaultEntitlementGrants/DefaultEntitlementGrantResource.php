<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants;

use BackedEnum;
use Filagora\Core\Filament\Concerns\GatedByAbility;
use Filagora\Core\Navigation\NavigationGroupRegistry;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages\CreateDefaultEntitlementGrant;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages\EditDefaultEntitlementGrant;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages\ListDefaultEntitlementGrants;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Schemas\DefaultEntitlementGrantForm;
use Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Tables\DefaultEntitlementGrantsTable;
use Filagora\Entitlements\Models\DefaultEntitlementGrant;
use Filagora\Entitlements\Support\Abilities;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DefaultEntitlementGrantResource extends Resource
{
    use GatedByAbility;

    protected static ?string $model = DefaultEntitlementGrant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?int $navigationSort = 50;

    protected static function abilityFor(string|UnitEnum $action, ?Model $record): ?string
    {
        return Abilities::MANAGE;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return app(NavigationGroupRegistry::class)->label('members');
    }

    public static function getNavigationLabel(): string
    {
        return __('filagora-entitlements::messages.default_grants.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filagora-entitlements::messages.default_grants.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filagora-entitlements::messages.default_grants.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return DefaultEntitlementGrantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DefaultEntitlementGrantsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDefaultEntitlementGrants::route('/'),
            'create' => CreateDefaultEntitlementGrant::route('/create'),
            'edit' => EditDefaultEntitlementGrant::route('/{record}/edit'),
        ];
    }
}
