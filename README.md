# Filagora Entitlements

Entitlement definitions, user and default grants, requests, a cached resolver and the
authorization service for Filagora, as a Laravel 13 / Filament 5 plugin. An *entitlement* is a
named permission such as `forums.read`; users hold them through grants, and everything else in
Filagora asks "may this user do this?" through one service. Requires
[`filagora/filagora-core`](https://github.com/filagora/filagora-core).

> `0.x`: the API may change between minor versions.

## Install

```bash
composer require filagora/filagora-entitlements
php artisan migrate
php artisan filagora:entitlements:sync   # creates definitions for the keys packages declare
```

Register both plugins on a Filament panel:

```php
->plugins([
    \Filagora\Core\FilagoraCorePlugin::make(),
    \Filagora\Entitlements\FilagoraEntitlementsPlugin::make(),
])
```

Publish the config if you want to change it: `php artisan vendor:publish --tag=filagora-entitlements-config`.

## What it provides

- **Tables and models** for definitions, user grants, default grants and requests
  (`EntitlementDefinition`, `UserEntitlementGrant`, `DefaultEntitlementGrant`, `EntitlementRequest`).
- **`EntitlementResolver`** works out what a user holds from default grants (all users,
  authenticated users or verified users), direct grants (with start, expiry and revocation) and
  grants other packages contribute. Wildcards work: a grant of `api.plugins.*` covers
  `api.plugins.read`. Results are cached per user under a versioned key that is invalidated the
  moment a grant changes, and memoised for the request. `explain()` says where an entitlement
  came from.
- **`AuthorizationService`**: `allows`, `allowsAny` and `allowsAll`, the single place that
  answers authorization questions. Policies can use the `AuthorizesWithEntitlements` trait. It is
  also bound as core's `AccessChecker`, which is what the other Filagora packages ask whether a
  user may use a staff screen, so installing this package is all they need. To use something else
  (roles, a permissions package), bind your own `Filagora\Core\Contracts\AccessChecker` in your
  application's service provider; the other packages then follow it.
- **Privacy.** Registers a `PrivacyContributor` with core: a person's grants and requests for an
  export, and their deletion (clearing the cache) for an erasure. With `filagora-compliance`
  installed, privacy requests use it.
- **Filament screens** for definitions, user grants, default grants and a request queue with
  approve, reject and revoke, plus two relation managers for your own user resource.
- **Actions and events** for reviewing requests: `ApproveEntitlementRequest`,
  `RejectEntitlementRequest`, `RevokeEntitlementRequest`, and the matching
  `EntitlementRequestApproved`, `EntitlementRequestRejected` and `EntitlementRequestRevoked` events.
- **`filagora:entitlements:sync`** creates a definition for every key packages declare or grants
  use. It only creates: a definition staff have edited is never overwritten.
- **`filagora:adopt`** support: the package ships a snapshot of its four tables, so a site that
  already has them can adopt them (see core).

## Access control

Every screen is **denied until the user holds the matching entitlement**. Each screen is guarded
by a Gate ability, and `gates` in the config maps the ability to the entitlement that opens it:

| Gate ability | Opens | Default entitlement key |
| --- | --- | --- |
| `filagora.entitlements.manage` | Definitions, user grants, default grants, the user relation manager for grants | `filagora.entitlements.manage` |
| `filagora.entitlements.view` | The request queue and reviewing requests, the user relation manager for requests | `filagora.entitlements.view` |
| `filagora.settings.manage` | Core's settings screen | `filagora.settings.manage` |

A single wildcard grant, `filagora.*`, opens all of them. To use your own keys, change the right
side of the map in `config/filagora-entitlements.php`:

```php
'gates' => [
    'filagora.entitlements.manage' => 'admin.entitlements.manage',
],
```

Keys in the map are created as definitions by `filagora:entitlements:sync`.

## Using it in your app

1. Add the optional trait to your user model: `use \Filagora\Entitlements\Concerns\HasEntitlements;`
   (the relations to grants and requests, and `hasEntitlement()`). Filagora never alters your
   `users` table.
2. Attach the relation managers to your own user resource:
   `UserEntitlementsRelationManager` and `UserEntitlementRequestsRelationManager`.
3. Ask questions through the service:

   ```php
   app(\Filagora\Entitlements\Services\AuthorizationService::class)->allows($user, 'forums.read');
   ```

   or `Gate::allows('filagora.entitlements.manage')` for the abilities above.

## Extending

- **Declare your keys**: `app(EntitlementRegistry::class)->define(new Definition('forums.read', label: 'Read forums'))`.
  Packages declare the entitlements they own this way; `EntitlementKeySource` lists further keys
  (for example those set on forums).
- **Grants from elsewhere** (a memberships add-on, say): implement `GrantSource` and register it
  with `EntitlementRegistry::grantSource()`. The resolver merges its grants with the rest.
- **A super-user bypass**: bind your own `SuperUserCheck` in the container to grant every
  entitlement to certain users (for example a role). The default bypasses nobody.

## Configuration

| Key | Environment | Default | Meaning |
| --- | --- | --- | --- |
| `cache_ttl_seconds` | `FILAGORA_ENTITLEMENTS_CACHE_TTL` | `300` | Upper bound on how long a user's resolved entitlements are cached (minimum 30). Changes made through the package take effect immediately regardless |
| `gates` | | the three above | Gate ability to entitlement key |

## Testing

```bash
composer test
```

Tests run against a real MariaDB database named `testing`.

## Licence

MIT. See [LICENSE](LICENSE).
