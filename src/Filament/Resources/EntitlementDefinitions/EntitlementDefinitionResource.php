<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use ForumKit\Core\Navigation\NavigationGroupRegistry;
use ForumKit\Entitlements\Filament\Concerns\GatedByAbility;
use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Pages\CreateEntitlementDefinition;
use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Pages\EditEntitlementDefinition;
use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Pages\ListEntitlementDefinitions;
use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Schemas\EntitlementDefinitionForm;
use ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Tables\EntitlementDefinitionsTable;
use ForumKit\Entitlements\Models\EntitlementDefinition;
use ForumKit\Entitlements\Support\Abilities;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EntitlementDefinitionResource extends Resource
{
    use GatedByAbility;

    protected static ?string $model = EntitlementDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 30;

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
        return __('forumkit-entitlements::messages.definitions.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('forumkit-entitlements::messages.definitions.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('forumkit-entitlements::messages.definitions.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return EntitlementDefinitionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EntitlementDefinitionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEntitlementDefinitions::route('/'),
            'create' => CreateEntitlementDefinition::route('/create'),
            'edit' => EditEntitlementDefinition::route('/{record}/edit'),
        ];
    }
}
