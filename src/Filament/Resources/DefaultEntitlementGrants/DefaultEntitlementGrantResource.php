<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use ForumKit\Core\Navigation\NavigationGroupRegistry;
use ForumKit\Entitlements\Filament\Concerns\GatedByAbility;
use ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages\CreateDefaultEntitlementGrant;
use ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages\EditDefaultEntitlementGrant;
use ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\Pages\ListDefaultEntitlementGrants;
use ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\Schemas\DefaultEntitlementGrantForm;
use ForumKit\Entitlements\Filament\Resources\DefaultEntitlementGrants\Tables\DefaultEntitlementGrantsTable;
use ForumKit\Entitlements\Models\DefaultEntitlementGrant;
use ForumKit\Entitlements\Support\Abilities;
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
        return __('forumkit-entitlements::messages.default_grants.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('forumkit-entitlements::messages.default_grants.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('forumkit-entitlements::messages.default_grants.plural_model_label');
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
