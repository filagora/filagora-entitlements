<?php

declare(strict_types=1);

use Filagora\Entitlements\Enums\DefaultEntitlementTarget;
use Filagora\Entitlements\Enums\EntitlementRequestStatus;
use Filagora\Entitlements\Models\DefaultEntitlementGrant;
use Filagora\Entitlements\Models\EntitlementDefinition;
use Filagora\Entitlements\Models\EntitlementRequest;
use Filagora\Entitlements\Models\UserEntitlementGrant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The support site's production database already holds these tables, and the packages must
 * be able to adopt them as they are. These tests pin each table's exact shape, taken from
 * the existing schema, so a migration change cannot drift from it.
 */

/**
 * Build the tables with an empty prefix, the way an adopting site names them.
 */
function createUnprefixedEntitlementTables(): void
{
    config(['filagora.table_prefix' => '']);

    $migration = require __DIR__.'/../../database/migrations/2026_10_10_100000_create_filagora_entitlement_tables.php';
    $migration->down();
    $migration->up();
}

/**
 * @return array<string, array{type: string, null: bool, default: string|null}>
 */
function columnShape(string $table): array
{
    $shape = [];

    foreach (Schema::getColumns($table) as $column) {
        $default = $column['default'];
        $shape[$column['name']] = [
            'type' => $column['type'],
            'null' => $column['nullable'],
            'default' => $default === null ? null : trim((string) $default, "'"),
        ];
    }

    return $shape;
}

/**
 * @return array<string, array{columns: list<string>, unique: bool}>
 */
function indexShape(string $table): array
{
    $shape = [];

    foreach (Schema::getIndexes($table) as $index) {
        $shape[$index['name']] = ['columns' => $index['columns'], 'unique' => $index['unique']];
    }

    ksort($shape);

    return $shape;
}

/**
 * @return array<string, array{columns: list<string>, on: string, delete: string}>
 */
function foreignShape(string $table): array
{
    $shape = [];

    foreach (Schema::getForeignKeys($table) as $key) {
        $shape[$key['name']] = [
            'columns' => $key['columns'],
            'on' => $key['foreign_table'],
            'delete' => strtolower($key['on_delete']),
        ];
    }

    ksort($shape);

    return $shape;
}

afterEach(function () {
    foreach (['entitlement_requests', 'user_entitlement_grants', 'default_entitlement_grants', 'entitlement_definitions'] as $table) {
        Schema::dropIfExists($table);
    }

    // Testbench rolls migrations back on teardown using the current config.
    config(['filagora.table_prefix' => 'filagora_']);
});

/**
 * @param  array<string, mixed>  $expected
 * @return array<string, mixed>
 */
function sorted(array $expected): array
{
    ksort($expected);

    return $expected;
}

it('matches the existing entitlement_definitions table', function () {
    createUnprefixedEntitlementTables();

    expect(columnShape('entitlement_definitions'))->toBe([
        'id' => ['type' => 'bigint(20) unsigned', 'null' => false, 'default' => null],
        'key' => ['type' => 'varchar(255)', 'null' => false, 'default' => null],
        'label' => ['type' => 'varchar(255)', 'null' => false, 'default' => null],
        'description' => ['type' => 'text', 'null' => true, 'default' => 'NULL'],
        'namespace' => ['type' => 'varchar(255)', 'null' => true, 'default' => 'NULL'],
        'is_grantable' => ['type' => 'tinyint(1)', 'null' => false, 'default' => '1'],
        'is_requestable' => ['type' => 'tinyint(1)', 'null' => false, 'default' => '0'],
        'supports_wildcard_children' => ['type' => 'tinyint(1)', 'null' => false, 'default' => '0'],
        'is_active' => ['type' => 'tinyint(1)', 'null' => false, 'default' => '1'],
        'sort_order' => ['type' => 'int(10) unsigned', 'null' => false, 'default' => '0'],
        'meta' => ['type' => 'longtext', 'null' => true, 'default' => 'NULL'],
        'created_at' => ['type' => 'timestamp', 'null' => true, 'default' => 'NULL'],
        'updated_at' => ['type' => 'timestamp', 'null' => true, 'default' => 'NULL'],
    ])->and(indexShape('entitlement_definitions'))->toBe(sorted([
        'entitlement_definitions_is_active_index' => ['columns' => ['is_active'], 'unique' => false],
        'entitlement_definitions_key_unique' => ['columns' => ['key'], 'unique' => true],
        'entitlement_definitions_namespace_index' => ['columns' => ['namespace'], 'unique' => false],
        'primary' => ['columns' => ['id'], 'unique' => true],
    ]));
});

it('matches the existing default_entitlement_grants table', function () {
    createUnprefixedEntitlementTables();

    expect(array_keys(columnShape('default_entitlement_grants')))->toBe([
        'id', 'entitlement_key', 'target_type', 'target_value', 'starts_at', 'expires_at', 'reason', 'meta', 'created_at', 'updated_at',
    ])->and(columnShape('default_entitlement_grants')['target_type'])->toBe(['type' => 'varchar(255)', 'null' => false, 'default' => null])
        ->and(columnShape('default_entitlement_grants')['target_value']['null'])->toBeTrue()
        ->and(indexShape('default_entitlement_grants'))->toBe(sorted([
            'default_entitlement_grants_entitlement_key_index' => ['columns' => ['entitlement_key'], 'unique' => false],
            'default_entitlement_grants_expires_at_index' => ['columns' => ['expires_at'], 'unique' => false],
            'default_entitlement_grants_starts_at_index' => ['columns' => ['starts_at'], 'unique' => false],
            'default_entitlement_grants_target_type_index' => ['columns' => ['target_type'], 'unique' => false],
            'default_entitlement_grants_target_value_index' => ['columns' => ['target_value'], 'unique' => false],
            'primary' => ['columns' => ['id'], 'unique' => true],
        ]));
});

it('matches the existing user_entitlement_grants table', function () {
    createUnprefixedEntitlementTables();

    $columns = columnShape('user_entitlement_grants');

    expect(array_keys($columns))->toBe([
        'id', 'user_id', 'entitlement_key', 'grant_type', 'source_type', 'source_id', 'granted_by_id', 'starts_at', 'expires_at',
        'revoked_at', 'revoked_by_id', 'reason', 'meta', 'created_at', 'updated_at',
    ])->and($columns['user_id'])->toBe(['type' => 'bigint(20) unsigned', 'null' => false, 'default' => null])
        ->and($columns['source_type']['null'])->toBeTrue()
        ->and($columns['source_id']['type'])->toBe('bigint(20) unsigned')
        ->and($columns['granted_by_id']['null'])->toBeTrue()
        ->and($columns['revoked_by_id']['null'])->toBeTrue()
        ->and(indexShape('user_entitlement_grants'))->toBe(sorted([
            'primary' => ['columns' => ['id'], 'unique' => true],
            'user_entitlement_grants_entitlement_key_index' => ['columns' => ['entitlement_key'], 'unique' => false],
            'user_entitlement_grants_expires_at_index' => ['columns' => ['expires_at'], 'unique' => false],
            'user_entitlement_grants_granted_by_id_foreign' => ['columns' => ['granted_by_id'], 'unique' => false],
            'user_entitlement_grants_grant_type_index' => ['columns' => ['grant_type'], 'unique' => false],
            'user_entitlement_grants_revoked_at_index' => ['columns' => ['revoked_at'], 'unique' => false],
            'user_entitlement_grants_revoked_by_id_foreign' => ['columns' => ['revoked_by_id'], 'unique' => false],
            'user_entitlement_grants_source_type_source_id_index' => ['columns' => ['source_type', 'source_id'], 'unique' => false],
            'user_entitlement_grants_starts_at_index' => ['columns' => ['starts_at'], 'unique' => false],
            'user_entitlement_grants_user_id_foreign' => ['columns' => ['user_id'], 'unique' => false],
        ]))->and(foreignShape('user_entitlement_grants'))->toBe([
            'user_entitlement_grants_granted_by_id_foreign' => ['columns' => ['granted_by_id'], 'on' => 'users', 'delete' => 'set null'],
            'user_entitlement_grants_revoked_by_id_foreign' => ['columns' => ['revoked_by_id'], 'on' => 'users', 'delete' => 'set null'],
            'user_entitlement_grants_user_id_foreign' => ['columns' => ['user_id'], 'on' => 'users', 'delete' => 'cascade'],
        ]);
});

it('matches the existing entitlement_requests table', function () {
    createUnprefixedEntitlementTables();

    $columns = columnShape('entitlement_requests');

    expect(array_keys($columns))->toBe([
        'id', 'user_id', 'entitlement_key', 'status', 'request_reason', 'review_notes', 'reviewed_by_id', 'requested_at',
        'reviewed_at', 'expires_at', 'meta', 'created_at', 'updated_at',
    ])->and($columns['status'])->toBe(['type' => 'varchar(255)', 'null' => false, 'default' => null])
        ->and($columns['reviewed_by_id']['null'])->toBeTrue()
        ->and(indexShape('entitlement_requests'))->toBe(sorted([
            'entitlement_requests_entitlement_key_index' => ['columns' => ['entitlement_key'], 'unique' => false],
            'entitlement_requests_expires_at_index' => ['columns' => ['expires_at'], 'unique' => false],
            'entitlement_requests_requested_at_index' => ['columns' => ['requested_at'], 'unique' => false],
            'entitlement_requests_reviewed_at_index' => ['columns' => ['reviewed_at'], 'unique' => false],
            'entitlement_requests_reviewed_by_id_foreign' => ['columns' => ['reviewed_by_id'], 'unique' => false],
            'entitlement_requests_status_index' => ['columns' => ['status'], 'unique' => false],
            'entitlement_requests_user_id_foreign' => ['columns' => ['user_id'], 'unique' => false],
            'primary' => ['columns' => ['id'], 'unique' => true],
        ]))->and(foreignShape('entitlement_requests'))->toBe([
            'entitlement_requests_reviewed_by_id_foreign' => ['columns' => ['reviewed_by_id'], 'on' => 'users', 'delete' => 'set null'],
            'entitlement_requests_user_id_foreign' => ['columns' => ['user_id'], 'on' => 'users', 'delete' => 'cascade'],
        ]);
});

it('stores meta as validated JSON like the existing tables', function () {
    createUnprefixedEntitlementTables();

    $definition = Schema::getColumns('entitlement_definitions');
    $meta = collect($definition)->firstWhere('name', 'meta');

    expect($meta['type_name'])->toBe('longtext');

    $row = EntitlementDefinition::create(['key' => 'a.b', 'label' => 'A', 'meta' => ['x' => 1]]);

    expect($row->refresh()->meta)->toBe(['x' => 1]);

    expect(fn () => DB::table('entitlement_definitions')->insert(['key' => 'bad', 'label' => 'Bad', 'meta' => '{not json']))
        ->toThrow(QueryException::class);
});

it('loads every stored enum value and a free-form grant type', function () {
    createUnprefixedEntitlementTables();
    $userId = DB::table('users')->insertGetId(['name' => 'U', 'email' => 'u@example.test', 'password' => 'x']);

    foreach (DefaultEntitlementTarget::cases() as $case) {
        DB::table('default_entitlement_grants')->insert(['entitlement_key' => 'k', 'target_type' => $case->value]);
    }

    foreach (EntitlementRequestStatus::cases() as $case) {
        DB::table('entitlement_requests')->insert(['user_id' => $userId, 'entitlement_key' => 'k', 'status' => $case->value]);
    }

    foreach (['manual', 'registration', 'approved_request', 'membership'] as $type) {
        DB::table('user_entitlement_grants')->insert(['user_id' => $userId, 'entitlement_key' => 'k', 'grant_type' => $type]);
    }

    // Legacy rows can carry a free-form, non-class source tag.
    DB::table('user_entitlement_grants')->insert([
        'user_id' => $userId, 'entitlement_key' => 'k', 'grant_type' => 'manual', 'source_type' => 'migration:backfill', 'source_id' => null,
    ]);

    expect(DefaultEntitlementGrant::all())->toHaveCount(3)
        ->and(EntitlementRequest::all()->pluck('status')->map->value->all())->toBe(['pending', 'approved', 'rejected', 'revoked'])
        ->and(UserEntitlementGrant::all())->toHaveCount(5);
});
