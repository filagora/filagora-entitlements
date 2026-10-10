<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Filament\Resources\UserEntitlementGrants\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use ForumKit\Core\Support\UserModel;
use ForumKit\Entitlements\Models\EntitlementDefinition;

class UserEntitlementGrantForm
{
    /**
     * The fields shared with the user relation manager (everything except the user).
     *
     * @return list<Component>
     */
    public static function grantFields(): array
    {
        $title = UserModel::titleAttribute();

        return [
            Select::make('entitlement_key')
                ->label(__('forumkit-entitlements::messages.user_grants.form.entitlement'))
                ->required()
                ->options(fn (): array => EntitlementDefinition::selectorOptions())
                ->searchable()
                ->preload(),
            Select::make('grant_type')
                ->required()
                ->options(self::grantTypes())
                ->default('manual'),
            Select::make('granted_by_id')
                ->relationship('grantedBy', $title)
                ->label(__('forumkit-entitlements::messages.user_grants.form.granted_by'))
                ->searchable()
                ->preload()
                ->default(fn (): int|string|null => auth()->id()),
            TextInput::make('source_type')
                ->maxLength(255)
                ->helperText(__('forumkit-entitlements::messages.user_grants.form.source_type_help')),
            TextInput::make('source_id')
                ->numeric()
                ->helperText(__('forumkit-entitlements::messages.user_grants.form.source_id_help')),
            DateTimePicker::make('starts_at')
                ->native(false)
                ->seconds(false),
            DateTimePicker::make('expires_at')
                ->native(false)
                ->seconds(false),
            DateTimePicker::make('revoked_at')
                ->native(false)
                ->seconds(false),
            Select::make('revoked_by_id')
                ->relationship('revokedBy', $title)
                ->label(__('forumkit-entitlements::messages.user_grants.form.revoked_by'))
                ->searchable()
                ->preload()
                ->visible(fn (Get $get): bool => filled($get('revoked_at'))),
            Textarea::make('reason')
                ->rows(3)
                ->columnSpanFull(),
            KeyValue::make('meta')
                ->label(__('forumkit-entitlements::messages.user_grants.form.metadata'))
                ->columnSpanFull(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', UserModel::titleAttribute())
                    ->label(__('forumkit-entitlements::messages.user_grants.form.user'))
                    ->required()
                    ->searchable()
                    ->preload(),
                ...self::grantFields(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public static function grantTypes(): array
    {
        return [
            'manual' => __('forumkit-entitlements::messages.user_grants.form.grant_type.manual'),
            'approved_request' => __('forumkit-entitlements::messages.user_grants.form.grant_type.approved_request'),
            'promotion' => __('forumkit-entitlements::messages.user_grants.form.grant_type.promotion'),
            'system' => __('forumkit-entitlements::messages.user_grants.form.grant_type.system'),
            'membership' => __('forumkit-entitlements::messages.user_grants.form.grant_type.membership'),
        ];
    }
}
