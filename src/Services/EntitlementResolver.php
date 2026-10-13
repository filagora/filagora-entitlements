<?php

declare(strict_types=1);

namespace Filagora\Entitlements\Services;

use Carbon\CarbonInterface;
use Filagora\Entitlements\Contracts\SuperUserCheck;
use Filagora\Entitlements\Enums\DefaultEntitlementTarget;
use Filagora\Entitlements\Models\DefaultEntitlementGrant;
use Filagora\Entitlements\Models\EntitlementRequest;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Filagora\Entitlements\Registry\EntitlementRegistry;
use Filagora\Entitlements\Support\EntitlementCache;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves which entitlements a user holds, from three kinds of source: grants
 * contributed by other packages, database-driven default grants, and direct user
 * grants. The result is cached per user under a versioned key, and memoised for
 * the life of this instance.
 *
 * Bind as `scoped`, so long-lived workers never carry payloads between requests.
 *
 * @phpstan-type Payload array{entitlements: list<string>, grants: list<array<string, mixed>>}
 */
class EntitlementResolver
{
    /**
     * Payloads already resolved in this process, keyed by user id. A single check costs
     * several cache round trips (user version, default version, payload read and write),
     * and listing pages check once per row, so this collapses that to one lookup per user.
     *
     * @var array<int, Payload>
     */
    private array $memoizedPayloads = [];

    public function __construct(
        private readonly EntitlementCache $cache,
        private readonly EntitlementRegistry $registry,
        private readonly SuperUserCheck $superUsers,
    ) {}

    /**
     * Drop memoised payloads so the next read re-resolves from the cache. Pass a user
     * id to forget one user, or omit it to clear everything.
     */
    public function flushMemoized(?int $userId = null): void
    {
        if ($userId === null) {
            $this->memoizedPayloads = [];

            return;
        }

        unset($this->memoizedPayloads[$userId]);
    }

    /**
     * @return list<string>
     */
    public function entitlementsFor(Model $user): array
    {
        return $this->resolvedPayloadFor($user)['entitlements'];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function grantsFor(Model $user): Collection
    {
        return collect($this->resolvedPayloadFor($user)['grants']);
    }

    /**
     * The grants that explain why a user holds an entitlement.
     *
     * @return list<array<string, mixed>>
     */
    public function explain(Model $user, string $entitlement): array
    {
        $requested = trim($entitlement);

        if ($requested === '') {
            return [];
        }

        return $this->grantsFor($user)
            ->filter(fn (array $grant): bool => $this->grantMatches((string) ($grant['entitlement_key'] ?? ''), $requested))
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function explanationsFor(Model $user): array
    {
        $explanations = [];

        foreach ($this->entitlementsFor($user) as $entitlement) {
            $explanations[$entitlement] = $this->explain($user, $entitlement);
        }

        return $explanations;
    }

    public function hasEntitlement(Model $user, string $entitlement): bool
    {
        $requested = trim($entitlement);

        if ($requested === '') {
            return false;
        }

        if ($this->superUsers->bypasses($user)) {
            return true;
        }

        foreach ($this->entitlementsFor($user) as $grant) {
            if ($this->grantMatches($grant, $requested)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Split grants into exact keys and wildcard prefixes (`a.b.*` becomes `a.b.`).
     *
     * @param  list<string>  $grants
     * @return array{exact: list<string>, wildcard_prefixes: list<string>}
     */
    public function partitionGrants(array $grants): array
    {
        $exact = [];
        $wildcardPrefixes = [];

        foreach ($this->normalizeEntitlements($grants) as $grant) {
            if ($this->isWildcardGrant($grant)) {
                $wildcardPrefixes[] = substr($grant, 0, -1);

                continue;
            }

            $exact[] = $grant;
        }

        return [
            'exact' => array_values(array_unique($exact)),
            'wildcard_prefixes' => array_values(array_unique($wildcardPrefixes)),
        ];
    }

    public function grantMatches(string $grant, string $requestedEntitlement): bool
    {
        $normalizedGrant = trim($grant);
        $normalizedRequested = trim($requestedEntitlement);

        if ($normalizedGrant === '' || $normalizedRequested === '') {
            return false;
        }

        if ($normalizedGrant === $normalizedRequested) {
            return true;
        }

        if (! $this->isWildcardGrant($normalizedGrant)) {
            return false;
        }

        return str_starts_with($normalizedRequested, substr($normalizedGrant, 0, -1));
    }

    /**
     * @return Payload
     */
    private function resolvedPayloadFor(Model $user): array
    {
        $userId = (int) $user->getKey();

        if (array_key_exists($userId, $this->memoizedPayloads)) {
            return $this->memoizedPayloads[$userId];
        }

        /** @var Payload $payload */
        $payload = $this->store()->remember(
            $this->cache->keyForUser($user),
            now()->addSeconds($this->cache->ttlSeconds()),
            fn (): array => $this->buildResolvedPayload($user),
        );

        return $this->memoizedPayloads[$userId] = $payload;
    }

    private function store(): Repository
    {
        return Cache::store();
    }

    /**
     * @return Payload
     */
    private function buildResolvedPayload(Model $user): array
    {
        $grants = [
            ...$this->externalGrantRecordsFor($user),
            ...$this->defaultGrantRecordsFor($user),
            ...$this->directGrantRecordsFor($user),
        ];

        return [
            'entitlements' => $this->normalizeEntitlements(array_map(
                static fn (array $grant): mixed => $grant['entitlement_key'] ?? null,
                $grants,
            )),
            'grants' => $grants,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function externalGrantRecordsFor(Model $user): array
    {
        $records = [];

        foreach ($this->registry->grantSources() as $source) {
            foreach ($source->grantsFor($user) as $record) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultGrantRecordsFor(Model $user): array
    {
        return DefaultEntitlementGrant::query()
            ->active()
            ->orderBy('id')
            ->get()
            ->filter(fn (DefaultEntitlementGrant $grant): bool => $this->defaultGrantApplies($grant, $user))
            ->map(fn (DefaultEntitlementGrant $grant): array => $this->defaultGrantRecord($grant))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function directGrantRecordsFor(Model $user): array
    {
        return UserEntitlementGrant::query()
            ->where('user_id', $user->getKey())
            ->active()
            ->with('grantedBy')
            ->get()
            ->map(fn (UserEntitlementGrant $grant): array => $this->directGrantRecord($grant))
            ->values()
            ->all();
    }

    private function defaultGrantApplies(DefaultEntitlementGrant $grant, Model $user): bool
    {
        return match ($grant->target_type) {
            DefaultEntitlementTarget::ALL_USERS, DefaultEntitlementTarget::AUTHENTICATED_USERS => true,
            DefaultEntitlementTarget::VERIFIED_USERS => $this->hasVerifiedEmail($user),
        };
    }

    private function hasVerifiedEmail(Model $user): bool
    {
        if ($user instanceof MustVerifyEmail) {
            return $user->hasVerifiedEmail();
        }

        return $user->getAttribute('email_verified_at') !== null;
    }

    private function isWildcardGrant(string $grant): bool
    {
        return str_ends_with($grant, '.*') && strlen($grant) > 2;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultGrantRecord(DefaultEntitlementGrant $grant): array
    {
        return [
            'entitlement_key' => (string) $grant->entitlement_key,
            'source_kind' => 'default_grant',
            'source_label' => 'Default grant',
            'source_summary' => match ($grant->target_type) {
                DefaultEntitlementTarget::ALL_USERS => 'Applies to all users',
                DefaultEntitlementTarget::AUTHENTICATED_USERS => 'Applies to authenticated users',
                DefaultEntitlementTarget::VERIFIED_USERS => 'Applies to verified users',
            },
            'source_reference' => 'Default grant #'.$grant->id,
            'grant_type' => 'default',
            'reason' => $grant->reason,
            'starts_at' => $this->serializeDate($grant->starts_at),
            'expires_at' => $this->serializeDate($grant->expires_at),
            'metadata' => [
                'default_grant_id' => (int) $grant->id,
                'target_type' => $grant->target_type->value,
                'target_value' => $grant->target_value,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function directGrantRecord(UserEntitlementGrant $grant): array
    {
        $grantedBy = $this->displayName($grant->grantedBy);

        return [
            'entitlement_key' => (string) $grant->entitlement_key,
            'source_kind' => 'user_grant',
            'source_label' => 'Direct grant',
            'source_summary' => $this->directGrantSummary($grant, $grantedBy),
            'source_reference' => 'Grant #'.$grant->id,
            'grant_type' => (string) $grant->grant_type,
            'reason' => $grant->reason,
            'starts_at' => $this->serializeDate($grant->starts_at),
            'expires_at' => $this->serializeDate($grant->expires_at),
            'metadata' => [
                'grant_id' => (int) $grant->id,
                'granted_by_id' => $grant->granted_by_id,
                'granted_by_username' => $grantedBy,
                'source_type' => $grant->source_type,
                'source_id' => $grant->source_id,
            ],
        ];
    }

    private function directGrantSummary(UserEntitlementGrant $grant, ?string $grantedBy): string
    {
        $segments = [];

        if (filled($grant->grant_type)) {
            $segments[] = ucfirst(str_replace('_', ' ', $grant->grant_type));
        }

        if ($grant->source_type === EntitlementRequest::class && $grant->source_id !== null) {
            $segments[] = 'from request #'.$grant->source_id;
        } elseif (filled($grant->source_type) && $grant->source_id !== null) {
            $segments[] = class_basename((string) $grant->source_type).' #'.$grant->source_id;
        }

        if ($grantedBy !== null) {
            $segments[] = 'granted by '.$grantedBy;
        }

        return $segments === [] ? 'Direct user entitlement grant' : implode(' • ', $segments);
    }

    /**
     * The user's `username` when the host has one, otherwise their `name`.
     */
    private function displayName(?Model $user): ?string
    {
        if ($user === null) {
            return null;
        }

        foreach (['username', 'name'] as $attribute) {
            $value = $user->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function serializeDate(?CarbonInterface $value): ?string
    {
        return $value?->toAtomString();
    }

    /**
     * @param  list<mixed>  $entitlements
     * @return list<string>
     */
    private function normalizeEntitlements(array $entitlements): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $entitlements,
        ))));
    }
}
