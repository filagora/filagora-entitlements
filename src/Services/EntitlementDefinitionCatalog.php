<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Services;

use ForumKit\Entitlements\Models\DefaultEntitlementGrant;
use ForumKit\Entitlements\Models\EntitlementDefinition;
use ForumKit\Entitlements\Models\UserEntitlementGrant;
use ForumKit\Entitlements\Registry\Definition;
use ForumKit\Entitlements\Registry\EntitlementRegistry;
use Illuminate\Support\Collection;

/**
 * Keeps the definitions table in step with the keys in use: those packages declare in
 * the registry, those their key sources list, and those already referenced by grants.
 * Existing definitions are never overwritten, so staff edits survive every sync.
 */
class EntitlementDefinitionCatalog
{
    public function __construct(private readonly EntitlementRegistry $registry) {}

    /**
     * @return list<string> The keys that were materialised.
     */
    public function synchronizeAll(): array
    {
        foreach ($this->registry->definitions() as $definition) {
            $this->upsertDeclared($definition);
        }

        $keys = [
            ...array_keys($this->registry->definitions()),
            ...$this->keySourceKeys(),
            ...$this->defaultGrantKeys(),
            ...$this->userGrantKeys(),
        ];

        return $this->materialize($keys);
    }

    public function synchronizeKey(?string $key): ?string
    {
        $normalized = $this->normalizeKey($key);

        if ($normalized === null) {
            return null;
        }

        $this->upsertDefinition($normalized);

        return $normalized;
    }

    /**
     * @param  list<string|null>  $keys
     * @return list<string>
     */
    public function materialize(array $keys): array
    {
        $normalizedKeys = array_values(array_unique(array_filter(array_map(
            fn (?string $key): ?string => $this->normalizeKey($key),
            $keys,
        ))));
        sort($normalizedKeys);

        foreach ($normalizedKeys as $key) {
            $this->upsertDefinition($key);
        }

        return $normalizedKeys;
    }

    /**
     * Create a definition from a package declaration. Hosts may later edit it in the
     * dashboard, so an existing row keeps its edited fields.
     */
    private function upsertDeclared(Definition $definition): void
    {
        EntitlementDefinition::query()->firstOrCreate(
            ['key' => $definition->key],
            [
                'label' => $definition->label ?? $definition->key,
                'description' => $definition->description,
                'namespace' => $this->namespaceOf($definition->key),
                'is_grantable' => $definition->grantable,
                'is_requestable' => $definition->requestable,
                'supports_wildcard_children' => $definition->wildcardChildren,
                'is_active' => true,
                'sort_order' => $definition->sortOrder,
            ],
        );
    }

    /**
     * Create a definition for a key only when none exists, so synchronising never
     * overwrites a label or flag that staff have edited.
     */
    private function upsertDefinition(string $key): void
    {
        EntitlementDefinition::query()->firstOrCreate(
            ['key' => $key],
            [
                'label' => $key,
                'namespace' => $this->namespaceOf($key),
                'supports_wildcard_children' => true,
                'is_grantable' => true,
                'is_requestable' => false,
                'is_active' => true,
                'sort_order' => 0,
            ],
        );
    }

    private function namespaceOf(string $key): ?string
    {
        $separator = strrpos($key, '.');

        return $separator === false ? null : substr($key, 0, $separator);
    }

    /**
     * @return list<string>
     */
    private function keySourceKeys(): array
    {
        $keys = [];

        foreach ($this->registry->keySources() as $source) {
            foreach ($source->keys() as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    private function defaultGrantKeys(): array
    {
        return $this->distinctKeys(DefaultEntitlementGrant::query()->pluck('entitlement_key'));
    }

    /**
     * @return list<string>
     */
    private function userGrantKeys(): array
    {
        return $this->distinctKeys(UserEntitlementGrant::query()->distinct()->pluck('entitlement_key'));
    }

    /**
     * @param  Collection<int, mixed>  $values
     * @return list<string>
     */
    private function distinctKeys(Collection $values): array
    {
        return $values
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeKey(?string $key): ?string
    {
        $normalized = trim((string) $key);

        return $normalized !== '' ? $normalized : null;
    }
}
