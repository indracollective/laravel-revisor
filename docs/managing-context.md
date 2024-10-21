# Managing Context

The `RevisorContext` determines which of your Model's tables are read/written to at any given time.

In practice, assuming you have a `Page` Revisor Model, the following `RevisorContexts`, when activated will result in
your Models querying the following tables:

| Revisor Context             | Table Name        |
|-----------------------------|-------------------|
| `RevisorContext::Draft`     | `pages_drafts`    |
| `RevisorContext::Published` | `pages_published` |
| `RevisorContext::Version`   | `pages_versions`  |

::: info NOTE
All examples below are in the context of `RevisorContext::Draft` and a Revisor-enabled `Page` Model.
:::

## Setting the Active Context

There are a few levels at which you may want to set the active `RevisorContext`. They are listed below in order of
precedence.

### 1. Global Default, via Config

By default, Revisor will use the `RevisorContext::Published` mode. This is recommended for most use cases, to avoid
unintentionally exposing draft records.

```php
// config/revisor.php
...
'default_context' => RevisorContext::Draft,
...
```

```php
// this will query the pages_drafts table
$page = Page::first();
```

### 2. Global Config Override, via Laravel Context

Revisor makes use of Laravel's [Context Management](https://laravel.com/docs/context) capabilities allowing you to
override the above Global Default `RevisorContext` as required, with visibility of the active
`RevisorContext` in logs and error pages.

Setting the active `RevisorContext` is best done through the `draftContext`, `publishedContext` and
`versionContext` methods on the `Revisor` Facade.

```php
use Indra\Revisor\Facades\Revisor;

Revisor::publishedContext();

// this will query the pages_published table

$page = Page::first();
```

::: info NOTE
Get the active global context with `Revisor::getContext()`, which falls back to the default context if not overridden.
:::

### 3. Local Override via Closures

To override both the Global `RevisorContexts` within an isolated scope, use the `withPublishedContext`, `withDraftContext`
or
`withVersionContext` method on the `Revisor` facade.

```php
use Indra\Revisor\Facades\Revisor;

Revisor::withPublishedContext(function () {
    // this will query the pages_published table
    $page = Page::first();
});
```

::: tip
Under the hood, this temporarily sets the `RevisorContext` in Laravel's Context Store, returning it to the previous
`RevisorContext` after the closure has been executed.
:::

### 4. Local Override on the Model / Query Builder via Query Scopes

Setting the `RevisorContext` on an Eloquent Model or Builder will override all other activated `RevisorContext` for that Model or
Builder instance only.

This can be achieved by using the Local Query Scope methods `withDraftContext`,
`withPublishedContext` or `withVersionContext` on your Model or Query Builder.

```php
Revisor::withPublishedContext(function() {
    Page::withDraftContext()->create([...]);
    $page = Page::withDraftContext()->first();
});
```

## Middleware

Revisor provides 3 Middleware Classes to help you set the `RevisorContext` on specific Routes or Route Groups.

### DraftMiddleware

Useful for routes that should primarily be used for editing Draft records, such as an admin panel.

```php
use Illuminate\Support\Facades\Route;
use Indra\Revisor\Middleware\DraftMiddleware;

Route::group('/admin', function () {
    ...
})->middleware(DraftMiddleware::class);
```

### DraftableMiddleware

Similar to `DraftMiddleware` but only activates `RevisorContext::Draft` if the Request contains a `?draft` query
parameter. This is useful for optionally enabling Draft mode on a Route/Group, ie. when allowing users to preview a draft
record.

### PublishedMiddleware

Similar to `DraftMiddleware` but for Published Records. Useful if your config sets the default mode to
`RevisorContext::Draft`
