# ForumKit Entitlements

Entitlement definitions, user and default grants, requests, a cached resolver and the
authorization service for ForumKit, as a Laravel 13 / Filament 5 plugin. Requires
[`zencart/forumkit-core`](https://github.com/zcwilt/forumkit-core).

> Work in progress: `0.x`, nothing released yet.

## Install

```bash
composer require zencart/forumkit-entitlements
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
