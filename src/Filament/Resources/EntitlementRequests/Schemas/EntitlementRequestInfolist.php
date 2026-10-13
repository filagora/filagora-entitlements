<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\EntitlementRequests\Schemas;

use Filagora\Core\Support\UserModel;
use Filagora\Entitlements\Enums\EntitlementRequestStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EntitlementRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $title = UserModel::titleAttribute();

        return $schema
            ->components([
                Section::make(__('filagora-entitlements::messages.requests.infolist.request_section'))
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make("user.{$title}")
                            ->label(__('filagora-entitlements::messages.requests.infolist.user'))
                            ->placeholder('-'),
                        TextEntry::make('entitlement_key')
                            ->label(__('filagora-entitlements::messages.requests.infolist.entitlement')),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof EntitlementRequestStatus ? $state->label() : (string) $state),
                        TextEntry::make('requested_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make("reviewedBy.{$title}")
                            ->label(__('filagora-entitlements::messages.requests.infolist.reviewed_by'))
                            ->placeholder('-'),
                        TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('expires_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('request_reason')
                            ->label(__('filagora-entitlements::messages.requests.infolist.request_reason'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('review_notes')
                            ->label(__('filagora-entitlements::messages.requests.infolist.review_notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('filagora-entitlements::messages.requests.infolist.metadata_section'))
                    ->schema([
                        TextEntry::make('meta')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                : ((string) $state !== '' ? (string) $state : '{}'))
                            ->columnSpanFull()
                            ->copyable(),
                    ])
                    ->columns(1),
            ]);
    }
}
