<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EntitlementDefinitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->helperText(__('forumkit-entitlements::messages.definitions.form.key_help'))
                    ->unique(ignoreRecord: true),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                TextInput::make('namespace')
                    ->maxLength(255)
                    ->helperText(__('forumkit-entitlements::messages.definitions.form.namespace_help')),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_grantable')
                    ->required()
                    ->default(true),
                Toggle::make('is_requestable')
                    ->required()
                    ->default(false),
                Toggle::make('supports_wildcard_children')
                    ->required()
                    ->default(false),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                KeyValue::make('meta')
                    ->label(__('forumkit-entitlements::messages.definitions.form.metadata'))
                    ->columnSpanFull(),
            ]);
    }
}
