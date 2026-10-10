<?php

declare(strict_types=1);

namespace ForumKit\Entitlements\Models;

use ForumKit\Core\Support\Tables;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string|null $description
 * @property string|null $namespace
 * @property bool $is_grantable
 * @property bool $is_requestable
 * @property bool $supports_wildcard_children
 * @property bool $is_active
 * @property int $sort_order
 * @property array<string, mixed>|null $meta
 */
class EntitlementDefinition extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'namespace',
        'is_grantable',
        'is_requestable',
        'supports_wildcard_children',
        'is_active',
        'sort_order',
        'meta',
    ];

    public function getTable(): string
    {
        return Tables::name('entitlement_definitions');
    }

    protected function casts(): array
    {
        return [
            'is_grantable' => 'boolean',
            'is_requestable' => 'boolean',
            'supports_wildcard_children' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * Active definition keys, in display order, as a key => key map for selects.
     *
     * @return array<string, string>
     */
    public static function selectorOptions(): array
    {
        $options = [];

        $keys = static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('key')
            ->pluck('key')
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->sort()
            ->values();

        foreach ($keys as $key) {
            $options[$key] = $key;
        }

        return $options;
    }
}
