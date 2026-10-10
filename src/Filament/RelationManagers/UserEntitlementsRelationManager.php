<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ForumKit\Core\Support\UserModel;
use ForumKit\Entitlements\Filament\Resources\UserEntitlementGrants\Schemas\UserEntitlementGrantForm;
use ForumKit\Entitlements\Support\Abilities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Attach to the host's own user resource to manage a user's direct entitlement grants.
 * The host user model needs the `HasEntitlements` trait (or its own `entitlementGrants`
 * relation).
 */
class UserEntitlementsRelationManager extends RelationManager
{
    protected static string $relationship = 'entitlementGrants';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('forumkit-entitlements::messages.relation_managers.grants.title');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows(Abilities::MANAGE);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(UserEntitlementGrantForm::grantFields());
    }

    public function table(Table $table): Table
    {
        $title = UserModel::titleAttribute();

        return $table
            ->recordTitleAttribute('entitlement_key')
            ->defaultSort('id', 'desc')
            ->description(__('forumkit-entitlements::messages.relation_managers.grants.description'))
            ->columns([
                TextColumn::make('entitlement_key')
                    ->label(__('forumkit-entitlements::messages.user_grants.table.entitlement'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grant_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make("grantedBy.{$title}")
                    ->label(__('forumkit-entitlements::messages.user_grants.table.granted_by'))
                    ->placeholder(__('forumkit-entitlements::messages.relation_managers.grants.system'))
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('revoked_at')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = $this->getOwnerRecord()->getKey();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
