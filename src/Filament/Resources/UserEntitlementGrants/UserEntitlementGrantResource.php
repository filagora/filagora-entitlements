<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\UserEntitlementGrants;

use BackedEnum;
use Filagora\Core\Navigation\NavigationGroupRegistry;
use Filagora\Entitlements\Filament\Concerns\GatedByAbility;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Pages\CreateUserEntitlementGrant;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Pages\EditUserEntitlementGrant;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Pages\ListUserEntitlementGrants;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Schemas\UserEntitlementGrantForm;
use Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Tables\UserEntitlementGrantsTable;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Support\Abilities;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserEntitlementGrantResource extends Resource
{
    use GatedByAbility;

    protected static ?string $model = UserEntitlementGrant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?int $navigationSort = 40;

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
        return __('filagora-entitlements::messages.user_grants.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filagora-entitlements::messages.user_grants.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filagora-entitlements::messages.user_grants.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return UserEntitlementGrantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserEntitlementGrantsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserEntitlementGrants::route('/'),
            'create' => CreateUserEntitlementGrant::route('/create'),
            'edit' => EditUserEntitlementGrant::route('/{record}/edit'),
        ];
    }
}
