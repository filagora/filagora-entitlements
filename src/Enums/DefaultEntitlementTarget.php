<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Enums;

enum DefaultEntitlementTarget: string
{
    case ALL_USERS = 'all_users';
    case AUTHENTICATED_USERS = 'authenticated_users';
    case VERIFIED_USERS = 'verified_users';

    public function label(): string
    {
        return match ($this) {
            self::ALL_USERS => 'All Users',
            self::AUTHENTICATED_USERS => 'Authenticated Users',
            self::VERIFIED_USERS => 'Verified Users',
        };
    }

    public function requiresTargetValue(): bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
