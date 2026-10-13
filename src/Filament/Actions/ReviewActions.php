<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filagora\Entitlements\Actions\ApproveEntitlementRequest;
use Filagora\Entitlements\Actions\RejectEntitlementRequest;
use Filagora\Entitlements\Actions\RevokeEntitlementRequest;
use Filagora\Entitlements\Enums\EntitlementRequestStatus;
use Filagora\Entitlements\Models\EntitlementRequest;
use Illuminate\Database\Eloquent\Model;

/**
 * The approve, reject and revoke actions, shared by the request screen and the user
 * relation manager so the two cannot drift apart.
 *
 * Pass `$record` to resolve the request when the action is not given one (the view
 * page), and `$onReviewed` to receive the updated request afterwards.
 */
final class ReviewActions
{
    /**
     * @return list<Action>
     */
    public static function all(?Closure $record = null, ?Closure $onReviewed = null): array
    {
        return [
            self::approve($record, $onReviewed),
            self::reject($record, $onReviewed),
            self::revoke($record, $onReviewed),
        ];
    }

    public static function approve(?Closure $record = null, ?Closure $onReviewed = null): Action
    {
        return Action::make('approve')
            ->label(__('filagora-entitlements::messages.requests.actions.approve'))
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->visible(fn (?Model $record_ = null): bool => self::resolve($record, $record_)->status === EntitlementRequestStatus::PENDING)
            ->schema([
                Textarea::make('review_notes')
                    ->label(__('filagora-entitlements::messages.requests.actions.review_notes'))
                    ->rows(4)
                    ->maxLength(5000),
                DateTimePicker::make('expires_at')
                    ->label(__('filagora-entitlements::messages.requests.actions.grant_expires_at'))
                    ->seconds(false),
            ])
            ->action(function (array $data, ?Model $record_ = null) use ($record, $onReviewed): void {
                $reviewed = app(ApproveEntitlementRequest::class)(
                    self::resolve($record, $record_),
                    self::reviewer(),
                    $data['review_notes'] ?? null,
                    $data['expires_at'] ?? null,
                );

                self::finish($reviewed, $onReviewed, 'approved');
            });
    }

    public static function reject(?Closure $record = null, ?Closure $onReviewed = null): Action
    {
        return Action::make('reject')
            ->label(__('filagora-entitlements::messages.requests.actions.reject'))
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->visible(fn (?Model $record_ = null): bool => self::resolve($record, $record_)->status === EntitlementRequestStatus::PENDING)
            ->schema([
                Textarea::make('review_notes')
                    ->label(__('filagora-entitlements::messages.requests.actions.review_notes'))
                    ->rows(4)
                    ->required()
                    ->maxLength(5000),
            ])
            ->action(function (array $data, ?Model $record_ = null) use ($record, $onReviewed): void {
                $reviewed = app(RejectEntitlementRequest::class)(
                    self::resolve($record, $record_),
                    self::reviewer(),
                    $data['review_notes'] ?? null,
                );

                self::finish($reviewed, $onReviewed, 'rejected');
            });
    }

    public static function revoke(?Closure $record = null, ?Closure $onReviewed = null): Action
    {
        return Action::make('revoke')
            ->label(__('filagora-entitlements::messages.requests.actions.revoke'))
            ->color('danger')
            ->icon('heroicon-o-no-symbol')
            ->visible(fn (?Model $record_ = null): bool => self::resolve($record, $record_)->status === EntitlementRequestStatus::APPROVED)
            ->schema([
                Textarea::make('review_notes')
                    ->label(__('filagora-entitlements::messages.requests.actions.revocation_notes'))
                    ->rows(4)
                    ->required()
                    ->maxLength(5000),
            ])
            ->action(function (array $data, ?Model $record_ = null) use ($record, $onReviewed): void {
                $reviewed = app(RevokeEntitlementRequest::class)(
                    self::resolve($record, $record_),
                    self::reviewer(),
                    $data['review_notes'] ?? null,
                );

                self::finish($reviewed, $onReviewed, 'revoked');
            });
    }

    private static function resolve(?Closure $resolver, ?Model $injected): EntitlementRequest
    {
        $request = $resolver !== null ? $resolver() : $injected;

        assert($request instanceof EntitlementRequest);

        return $request;
    }

    private static function reviewer(): Model
    {
        $reviewer = auth()->user();

        abort_unless($reviewer instanceof Model, 403);

        return $reviewer;
    }

    private static function finish(EntitlementRequest $reviewed, ?Closure $onReviewed, string $outcome): void
    {
        if ($onReviewed !== null) {
            $onReviewed($reviewed);
        }

        Notification::make()
            ->title(__("filagora-entitlements::messages.requests.notifications.{$outcome}"))
            ->success()
            ->send();
    }
}
