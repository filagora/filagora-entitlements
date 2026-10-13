<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\EntitlementRequests\Tables;

use Filagora\Core\Support\UserModel;
use Filagora\Entitlements\Enums\EntitlementRequestStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EntitlementRequestsTable
{
    public static function configure(Table $table): Table
    {
        $title = UserModel::titleAttribute();

        return $table
            ->defaultSort('requested_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make("user.{$title}")
                    ->label(__('filagora-entitlements::messages.requests.table.user'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entitlement_key')
                    ->label(__('filagora-entitlements::messages.requests.table.entitlement'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof EntitlementRequestStatus ? $state->label() : (string) $state),
                TextColumn::make('requested_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make("reviewedBy.{$title}")
                    ->label(__('filagora-entitlements::messages.requests.table.reviewed_by'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EntitlementRequestStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->recordActionsPosition(RecordActionsPosition::BeforeCells);
    }
}
