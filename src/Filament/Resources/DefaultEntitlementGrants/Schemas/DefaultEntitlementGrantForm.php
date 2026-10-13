<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Schemas;

use Filagora\Entitlements\Enums\DefaultEntitlementTarget;
use Filagora\Entitlements\Models\EntitlementDefinition;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DefaultEntitlementGrantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('entitlement_key')
                    ->label(__('filagora-entitlements::messages.default_grants.form.entitlement'))
                    ->required()
                    ->options(fn (): array => EntitlementDefinition::selectorOptions())
                    ->searchable()
                    ->preload(),
                Select::make('target_type')
                    ->required()
                    ->options(DefaultEntitlementTarget::options())
                    ->default(DefaultEntitlementTarget::AUTHENTICATED_USERS->value),
                DateTimePicker::make('starts_at')
                    ->native(false)
                    ->seconds(false),
                DateTimePicker::make('expires_at')
                    ->native(false)
                    ->seconds(false),
                Textarea::make('reason')
                    ->rows(3)
                    ->columnSpanFull(),
                KeyValue::make('meta')
                    ->label(__('filagora-entitlements::messages.default_grants.form.metadata'))
                    ->columnSpanFull(),
            ]);
    }
}
