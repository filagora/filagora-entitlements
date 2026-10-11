# ForumKit Entitlements

Entitlement definitions, user and default grants, requests, a cached resolver and the
authorization service for ForumKit, as a Laravel 13 / Filament 5 plugin. Requires
`zcwilt/forumkit-core` ([zcwilt/forumkit-core](https://github.com/zcwilt/forumkit-core) on GitHub).

> `0.x`: the API may change between minor versions.

## Install

```bash
composer require zcwilt/forumkit-entitlements
php artisan vendor:publish --tag=forumkit-entitlements-config
php artisan migrate
```

```php
->plugin(\ForumKit\Entitlements\ForumKitEntitlementsPlugin::make())
```

## Testing

```bash
composer test
```

Tests run against a real MariaDB database named `testing`.

## Licence

MIT. See [LICENSE](LICENSE).
