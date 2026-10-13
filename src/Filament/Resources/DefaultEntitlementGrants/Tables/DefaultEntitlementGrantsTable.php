<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\DefaultEntitlementGrants\Tables;

use Filagora\Entitlements\Enums\DefaultEntitlementTarget;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class DefaultEntitlementGrantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('entitlement_key')
                    ->label(__('filagora-entitlements::messages.default_grants.table.entitlement'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_type')
                    ->badge()
                    ->formatStateUsing(fn (DefaultEntitlementTarget|string|null $state): string => $state instanceof DefaultEntitlementTarget
                        ? $state->label()
                        : DefaultEntitlementTarget::tryFrom((string) $state)?->label() ?? (string) $state),
                TextColumn::make('target_value')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
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
