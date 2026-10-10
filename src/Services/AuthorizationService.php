<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Services;

use ForumKit\Core\Support\UserModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * The single place that answers "may this user do this?" with entitlements.
 * Policies, gates and middleware call this; nothing re-implements the checks.
 */
class AuthorizationService
{
    public function __construct(private readonly EntitlementResolver $resolver) {}

    public function allows(?Authenticatable $user, string $entitlement): bool
    {
        return $this->isHostUser($user)
            && $this->resolver->hasEntitlement($user, $entitlement);
    }

    /**
     * @param  list<string>  $entitlements
     */
    public function allowsAny(?Authenticatable $user, array $entitlements): bool
    {
        if (! $this->isHostUser($user)) {
            return false;
        }

        foreach ($entitlements as $entitlement) {
            if ($this->resolver->hasEntitlement($user, $entitlement)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $entitlements
     */
    public function allowsAll(?Authenticatable $user, array $entitlements): bool
    {
        if (! $this->isHostUser($user)) {
            return false;
        }

        foreach ($entitlements as $entitlement) {
            if (! $this->resolver->hasEntitlement($user, $entitlement)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @phpstan-assert-if-true Model&Authenticatable $user
     */
    private function isHostUser(?Authenticatable $user): bool
    {
        $class = UserModel::class();

        return $user instanceof Model && $user instanceof $class;
    }
}
