# Laravel Revisor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/IndrasLab/laravel-revisor.svg?style=flat-square)](https://packagist.org/packages/indra/laravel-revisor)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/IndrasLab/laravel-revisor/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/indra/laravel-revisor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/IndrasLab/laravel-revisor/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/indra/laravel-revisor/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/IndrasLab/laravel-revisor.svg?style=flat-square)](https://packagist.org/packages/indra/laravel-revisor)

## Robust drafting, publishing and version-tracking for Laravel Eloquent Models.

Laravel Revisor aims to provide the maximum power and flexibility possible in versioned record management, while
maintaining a very low tolerance complexity. To achieve this, it offers:

✅ Separate, complete database tables for draft, published and version history records of each Model

✅ Migration API for easily creating/modifying draft, published and version history tables

✅ Easy context management for setting the appropriate reading/writing mode at all levels of operation, from global
config, to middleware, mode callbacks and query builder level.

✅ Clean, flexible API for drafting, publishing and version management

✅ High configurability and excellent documentation

## Installation

You can install the package via composer:

```bash
composer require indra/laravel-revisor
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-revisor-config"
```

This is the contents of the published config file:

```php
return [
    'default_mode' => RevisorMode::Published,
    'table_suffixes' => [
        RevisorMode::Draft->value => '_drafts',
        RevisorMode::Version->value => '_versions',
        RevisorMode::Published->value => '_published',
    ],
    'publishing' => [
        'publish_on_created' => false,
        'publish_on_updated' => false,
    ],
    'versioning' => [
        'record_new_version_on_created' => true,
        'record_new_version_on_updated' => true,
        'keep_versions' => 10,
    ],
];
```

## Read the docs

[indraslab.github.io/laravel-revisor](https://indraslab.github.io/laravel-revisor/)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you've found a bug regarding security please mail shea@livesource.co.nz instead of using the issue tracker.

## Credits

- [Shea Dawson](https://github.com/sheadawson)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
