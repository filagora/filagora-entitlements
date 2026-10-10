<?php

declare(strict_types=1);

use ForumKit\Core\Schema\Adopter;
use ForumKit\Core\Schema\AdoptionStatus;
use ForumKit\Core\Schema\SchemaRegistry;
use ForumKit\Core\Schema\TableShape;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const ENTITLEMENT_MIGRATION = '2026_10_10_100000_create_forumkit_entitlement_tables';
const ENTITLEMENT_TABLES = ['entitlement_requests', 'user_entitlement_grants', 'default_entitlement_grants', 'entitlement_definitions'];

function entitlementsSchema()
{
    return app(SchemaRegistry::class)->get('entitlements');
}

/**
 * Build the tables the way an existing site has them (no prefix) and forget that the
 * package's migration ran, as it would not have there.
 */
function existingSiteTables(): void
{
    config(['forumkit.table_prefix' => '']);

    $migration = require __DIR__.'/../../database/migrations/'.ENTITLEMENT_MIGRATION.'.php';
    $migration->down();
    $migration->up();

    DB::table('migrations')->where('migration', ENTITLEMENT_MIGRATION)->delete();
}

afterEach(function () {
    foreach (ENTITLEMENT_TABLES as $table) {
        Schema::dropIfExists($table);
    }

    config(['forumkit.table_prefix' => 'forumkit_']);
});

it('ships a snapshot that matches its migration, column order included', function () {
    $snapshot = entitlementsSchema();

    expect($snapshot)->not->toBeNull()
        ->and(array_keys($snapshot->tables))->toBe(['entitlement_definitions', 'default_entitlement_grants', 'user_entitlement_grants', 'entitlement_requests'])
        ->and($snapshot->migrationNames())->toBe([ENTITLEMENT_MIGRATION]);

    foreach ($snapshot->tables as $base => $expected) {
        expect(TableShape::inspect($base)->differencesFrom($expected, compareColumnOrder: true))->toBe([], $base);
    }
});

it('adopts the tables of an existing site and records the migration', function () {
    existingSiteTables();

    $plan = app(Adopter::class)->planFor(entitlementsSchema());

    expect($plan->status)->toBe(AdoptionStatus::Adoptable, implode('; ', $plan->differences))
        ->and($plan->pendingMigrations)->toBe([ENTITLEMENT_MIGRATION]);

    $this->artisan('forumkit:adopt', ['--package' => 'entitlements'])->expectsOutputToContain('Recorded 1 migration(s) as run.')->assertExitCode(0);

    expect(DB::table('migrations')->where('migration', ENTITLEMENT_MIGRATION)->exists())->toBeTrue();
});

it('refuses an existing site whose grants table differs, naming the difference', function () {
    existingSiteTables();
    Schema::table('user_entitlement_grants', fn ($table) => $table->string('reason', 100)->nullable()->change());
    Schema::table('default_entitlement_grants', fn ($table) => $table->dropIndex(['target_type']));

    $this->artisan('forumkit:adopt', ['--package' => 'entitlements'])
        ->expectsOutputToContain('user_entitlement_grants: column reason type is varchar(100), expected text')
        ->expectsOutputToContain('default_entitlement_grants: index default_entitlement_grants_target_type_index is missing')
        ->assertExitCode(1);

    expect(DB::table('migrations')->where('migration', ENTITLEMENT_MIGRATION)->exists())->toBeFalse();
});

it('refuses when the foreign key delete rule differs', function () {
    existingSiteTables();
    Schema::table('entitlement_requests', function ($table) {
        $table->dropForeign(['user_id']);
        $table->foreign('user_id')->references('id')->on('users');
    });

    $plan = app(Adopter::class)->planFor(entitlementsSchema());

    expect($plan->status)->toBe(AdoptionStatus::Mismatch)
        ->and($plan->differences)->toContain('entitlement_requests: foreign key entitlement_requests_user_id_foreign on_delete is restrict, expected cascade');
});

it('is already adopted on a site that ran the package migration', function () {
    expect(app(Adopter::class)->planFor(entitlementsSchema())->status)->toBe(AdoptionStatus::AlreadyAdopted);
});
