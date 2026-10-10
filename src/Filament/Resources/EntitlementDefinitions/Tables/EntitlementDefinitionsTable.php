<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\EntitlementDefinitions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class EntitlementDefinitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('namespace')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                IconColumn::make('is_grantable')
                    ->boolean(),
                IconColumn::make('is_requestable')
                    ->boolean(),
                IconColumn::make('supports_wildcard_children')
                    ->boolean()
                    ->label(__('forumkit-entitlements::messages.definitions.table.wildcard')),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->recordActionsPosition(RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
