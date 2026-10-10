<?php

declare(strict_types=1);

use ForumKit\Core\Support\Tables;
use ForumKit\Core\Support\UserKey;
use ForumKit\Core\Support\UserModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::name('entitlement_definitions'), function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('namespace')->nullable()->index();
            $table->boolean('is_grantable')->default(true);
            $table->boolean('is_requestable')->default(false);
            $table->boolean('supports_wildcard_children')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create(Tables::name('default_entitlement_grants'), function (Blueprint $table): void {
            $table->id();
            $table->string('entitlement_key')->index();
            $table->string('target_type')->index();
            $table->string('target_value')->nullable()->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create(Tables::name('user_entitlement_grants'), function (Blueprint $table): void {
            $table->id();
            UserKey::column($table)->constrained(UserModel::table())->cascadeOnDelete();
            $table->string('entitlement_key')->index();
            $table->string('grant_type')->index();
            $table->nullableMorphs('source');
            UserKey::column($table, 'granted_by_id')->nullable()->constrained(UserModel::table())->nullOnDelete();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            UserKey::column($table, 'revoked_by_id')->nullable()->constrained(UserModel::table())->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create(Tables::name('entitlement_requests'), function (Blueprint $table): void {
            $table->id();
            UserKey::column($table)->constrained(UserModel::table())->cascadeOnDelete();
            $table->string('entitlement_key')->index();
            $table->string('status')->index();
            $table->text('request_reason')->nullable();
            $table->text('review_notes')->nullable();
            UserKey::column($table, 'reviewed_by_id')->nullable()->constrained(UserModel::table())->nullOnDelete();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('entitlement_requests'));
        Schema::dropIfExists(Tables::name('user_entitlement_grants'));
        Schema::dropIfExists(Tables::name('default_entitlement_grants'));
        Schema::dropIfExists(Tables::name('entitlement_definitions'));
    }
};
