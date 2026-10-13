<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Resources\EntitlementRequests;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filagora\Core\Navigation\NavigationGroupRegistry;
use Filagora\Entitlements\Enums\EntitlementRequestStatus;
use Filagora\Entitlements\Filament\Concerns\GatedByAbility;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\Pages\ListEntitlementRequests;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\Pages\ViewEntitlementRequest;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\Schemas\EntitlementRequestInfolist;
use Filagora\Entitlements\Filament\Resources\EntitlementRequests\Tables\EntitlementRequestsTable;
use Filagora\Entitlements\Models\EntitlementRequest;
use Filagora\Entitlements\Support\Abilities;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use UnitEnum;

/**
 * Staff queue for entitlement requests. Viewing and reviewing need the VIEW ability;
 * requests are created by users elsewhere and are never edited or deleted here.
 */
class EntitlementRequestResource extends Resource
{
    use GatedByAbility;

    protected static ?string $model = EntitlementRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 50;

    protected static function abilityFor(string|UnitEnum $action, ?Model $record): ?string
    {
        return in_array($action, ['viewAny', 'view', 'update'], true) ? Abilities::VIEW : null;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return app(NavigationGroupRegistry::class)->label('queues');
    }

    public static function getNavigationLabel(): string
    {
        return __('filagora-entitlements::messages.requests.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filagora-entitlements::messages.requests.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filagora-entitlements::messages.requests.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EntitlementRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EntitlementRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'reviewedBy']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEntitlementRequests::route('/'),
            'view' => ViewEntitlementRequest::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getPendingRequestCount();

        return $count > 0 ? ($count > 99 ? '99+' : (string) $count) : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    /**
     * Pending requests, counted once per HTTP request however often the navigation asks.
     */
    public static function getPendingRequestCount(): int
    {
        $request = app()->bound('request') ? app(Request::class) : null;
        $key = 'filagora.entitlements.pending_request_count';

        if ($request instanceof Request && $request->attributes->has($key)) {
            return (int) $request->attributes->get($key);
        }

        $count = EntitlementRequest::query()
            ->where('status', EntitlementRequestStatus::PENDING->value)
            ->count();

        $request?->attributes->set($key, $count);

        return $count;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
