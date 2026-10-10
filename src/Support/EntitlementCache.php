<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Support;

use ForumKit\Entitlements\Services\EntitlementResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache keys for resolved entitlements. A payload is cached under a key
 * that embeds the user's version, the defaults version and the user's updated_at,
 * so bumping a version invalidates every affected payload at once.
 */
final class EntitlementCache
{
    public function keyForUser(Model $user): string
    {
        $userId = (int) $user->getKey();
        $updatedAt = $user->getAttribute('updated_at');

        return sprintf(
            'entitlements:user:%d:user-v%d:default-v%d:model-v%d',
            $userId,
            $this->currentUserVersion($userId),
            $this->currentDefaultVersion(),
            $updatedAt instanceof \DateTimeInterface ? $updatedAt->getTimestamp() : 0,
        );
    }

    public function ttlSeconds(): int
    {
        $configured = config('forumkit-entitlements.cache_ttl_seconds', 300);

        return max(30, is_numeric($configured) ? (int) $configured : 300);
    }

    public function bumpUserVersion(int $userId): int
    {
        $version = $this->currentUserVersion($userId) + 1;
        Cache::forever($this->userVersionKey($userId), $version);
        $this->forgetMemoizedPayload($userId);

        return $version;
    }

    public function bumpDefaultVersion(): int
    {
        $version = $this->currentDefaultVersion() + 1;
        Cache::forever($this->defaultVersionKey(), $version);
        $this->forgetMemoizedPayload();

        return $version;
    }

    public function currentUserVersion(int $userId): int
    {
        return max(1, (int) Cache::get($this->userVersionKey($userId), 1));
    }

    public function currentDefaultVersion(): int
    {
        return max(1, (int) Cache::get($this->defaultVersionKey(), 1));
    }

    /**
     * The shared cache is invalidated by the version bump, but the resolver also
     * memoises payloads in-process; without this a grant made earlier in a request
     * would not be visible to a check made later in the same one.
     */
    private function forgetMemoizedPayload(?int $userId = null): void
    {
        if (! app()->resolved(EntitlementResolver::class)) {
            return;
        }

        app(EntitlementResolver::class)->flushMemoized($userId);
    }

    private function userVersionKey(int $userId): string
    {
        return 'entitlements:user-version:'.$userId;
    }

    private function defaultVersionKey(): string
    {
        return 'entitlements:default-version';
    }
}
