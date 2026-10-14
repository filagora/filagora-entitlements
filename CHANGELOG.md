# Changelog

All notable changes to this package are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [0.2.2] - 2026-10-14

### Added

- **Privacy contribution**, registered with core's `PrivacyContributorRegistry` so the compliance package (or any host) can export and erase without this package knowing it: `EntitlementsDataContributor` exports a person's grants and requests, and erases them one by one, so the cached entitlements are cleared as they go. Nothing here needs `filagora-compliance`.

## [0.2.1] - 2026-10-14

### Changed

- `AuthorizationService` is now the `Filagora\Core\Contracts\AccessChecker` the other packages ask (bound by this package, replacing core's deny-everyone default). A host that wants its own permission system binds its own checker instead. Behaviour with entitlements installed is unchanged.
- The resources use the Filament trait `Filagora\Core\Filament\Concerns\GatedByAbility` and the gates come from core's `GateAbilities`. Requires `filagora/filagora-core` `^0.2.1`.

### Deprecated

- `Filagora\Entitlements\Filament\Concerns\GatedByAbility`: use the core trait. The old name still works.

## [0.2.0] - 2026-10-13

### Changed

- **Renamed to Filagora.** The Composer package is now `filagora/filagora-entitlements` (it was `zcwilt/forumkit-entitlements`) and the repository is `github.com/filagora/filagora-entitlements`. The PHP namespace is `Filagora\Entitlements\` (was `ForumKit\Entitlements\`), the config file is `config/filagora-entitlements.php`, the default entitlement keys and Gate abilities start with `filagora.` (for example `filagora.entitlements.manage`), the command is `filagora:entitlements:sync`, and the table prefix is `filagora_`. It requires `filagora/filagora-core` `^0.2`. Nothing else changed: moving from 0.1 means renaming those names in your application, its config and its stored keys.


## [0.1.1] - 2026-10-11

### Changed

- **The Composer package is now `zcwilt/forumkit-entitlements`** (it was `zencart/forumkit-entitlements`), matching the GitHub owner, and it requires `zcwilt/forumkit-core` `^0.1`. Change your `composer.json` requirements to the new names; the code, the `ForumKit\Entitlements\` namespace and the API are unchanged. The old `zencart/...` packages are abandoned.

## [0.1.0] - 2026-10-10

First release. Requires `zencart/forumkit-core` `^0.1`. The API may change between `0.x` minor versions.

### Added

- Tables, models and enums for entitlement definitions, user grants, default grants and requests, matching the schema of the site they were extracted from, with a schema snapshot so `forumkit:adopt` can adopt an existing database.
- `EntitlementResolver`: resolves a user's entitlements from default grants, direct grants and grants contributed by other packages; wildcard (`a.b.*`) matching; per-user versioned cache with immediate invalidation; per-request memoisation; `explain()` for provenance.
- `AuthorizationService` (`allows`, `allowsAny`, `allowsAll`) as the single place that answers authorization questions, the `AuthorizesWithEntitlements` trait for policies, and the optional `HasEntitlements` trait for the host user model.
- Extension points: `EntitlementRegistry` (package-declared definitions, key sources and grant sources), `GrantSource`, `EntitlementKeySource` and `SuperUserCheck`.
- Definition catalogue and `forumkit:entitlements:sync`; a definition is created when missing and never overwritten, so staff edits survive.
- Approve, reject and revoke request actions, with `EntitlementRequestApproved`, `EntitlementRequestRejected` and `EntitlementRequestRevoked` events.
- Gate bridge: `forumkit-entitlements.gates` maps Gate abilities (`forumkit.settings.manage`, `forumkit.entitlements.manage`, `forumkit.entitlements.view`) to entitlement keys, which a host can remap to its own.
- Filament resources for definitions, user grants, default grants and requests (with review actions), and user relation managers for grants and requests. All are denied until the matching ability is defined.
