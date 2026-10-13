<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\UserEntitlementGrants\Tables;

use Filagora\Core\Support\UserModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class UserEntitlementGrantsTable
{
    public static function configure(Table $table): Table
    {
        $title = UserModel::titleAttribute();

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make("user.{$title}")
                    ->label(__('filagora-entitlements::messages.user_grants.table.user'))
                    ->searchable(),
                TextColumn::make('entitlement_key')
                    ->label(__('filagora-entitlements::messages.user_grants.table.entitlement'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grant_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make("grantedBy.{$title}")
                    ->label(__('filagora-entitlements::messages.user_grants.table.granted_by'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('revoked_at')
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
