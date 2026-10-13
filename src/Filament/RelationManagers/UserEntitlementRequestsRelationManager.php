<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\RelationManagers;

use Filagora\Core\Support\UserModel;
use Filagora\Entitlements\Enums\EntitlementRequestStatus;
use Filagora\Entitlements\Filament\Actions\ReviewActions;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\EntitlementRequestResource;
use Filagora\Entitlements\Support\Abilities;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Attach to the host's own user resource to review a user's entitlement requests in
 * context. The host user model needs the `HasEntitlements` trait (or its own
 * `entitlementRequests` relation).
 */
class UserEntitlementRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'entitlementRequests';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filagora-entitlements::messages.relation_managers.requests.title');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows(Abilities::VIEW);
    }

    public function table(Table $table): Table
    {
        $title = UserModel::titleAttribute();

        return $table
            ->recordTitleAttribute('entitlement_key')
            ->defaultSort('requested_at', 'desc')
            ->description(__('filagora-entitlements::messages.relation_managers.requests.description'))
            ->columns([
                TextColumn::make('entitlement_key')
                    ->label(__('filagora-entitlements::messages.requests.table.entitlement'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof EntitlementRequestStatus ? $state->label() : (string) $state),
                TextColumn::make('requested_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make("reviewedBy.{$title}")
                    ->label(__('filagora-entitlements::messages.requests.table.reviewed_by'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('view_request')
                    ->label(__('filagora-entitlements::messages.relation_managers.requests.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn ($record): string => EntitlementRequestResource::getUrl('view', ['record' => $record])),
                ...ReviewActions::all(),
            ]);
    }

    protected function canCreate(): bool
    {
        return false;
    }
}
