# Modes & Middleware

## RevisorContexts

Each of your Revisor-enabled Models have 3 distinct database tables ie. a Pages model would have
`pages_drafts`, `pages_published` and `pages_versions` tables.

**Which of these tables is read/written to by Laravel depends on which `RevisorContext` is currently active.**

There are 3 modes available on the `RevisorContext` enum which correspond to the 3 tables. They are

- `RevisorContext::Draft`
- `RevisorContext::Published`
- `RevisorContext::Version`

## Setting the RevisorContext

There are multiple levels at which you can set the active `RevisorContext`

### 1. Globally, via the config file

By default, Revisor will use the `RevisorContext::Published` mode. This is recommended for most use cases, to avoid
unintentionally exposing draft records.

```php
// this will query the pages_published table

$page = Page::first(); 
```

### 2. Globally on the Revisor Facade

Setting the mode globally via the `Revisor` facade will override the global default set in the config file.

```php
use Indra\Revisor\Facades\Revisor;

// Global mode is set to `RevisorContext::Published`

Revisor::setContext(RevisorContext::Draft);

// this will query the pages_drafts table

$page = Page::first();
```

### 3. Locally via Closures

To override both the above Global modes inside a Closure, use the `withPublishedContext`, `withDraftContext` or
`withVersionRecords` method on the `Revisor` facade.

```php
use Indra\Revisor\Facades\Revisor;

// Global mode is set to `RevisorContext::Published`

Revisor::withDraftContext(function () {
    
    // this will query the pages_drafts table
    
    $page = Page::first(); 
});
```

::: tip
Under the hood, this temporarily sets the `Revisor` Facade's mode, returning it to the previous mode after the closure
is run.   
:::

### 4. Locally on the Model / Query Builder via Scopes

Setting the RevisorContext on a Model or Query will override any other mode settings for that Model or
Builder instance. This can be achieved by using the Local Query Scope methods `withDraftContext`,
`withPublishedContext` or `withVersionRecords` on your Model or Query Builder.

```php
// Global mode is set to `RevisorContext::Published`

// this will create and retrieve a draft record

Page::withDraftContext()->create([...]);

$page = Page::withDraftContext()->first(); 
```

## Middleware

Revisor provides three Middlewares to help you set the RevisorContext on specific routes.

### DraftMiddleware

Useful for routes that should primarily be used for editing Draft records

```php
use Illuminate\Support\Facades\Route;
use Indra\Revisor\Middleware\DraftMiddleware;

Route::group('/admin', function () {
    ...
})->middleware(DraftMiddleware::class);
```

### DraftableMiddleware

Similar to `DraftMiddleware` but only activates `RevisorContext::Draft` if the Request contains a `?draft` query
parameter.
Useful for optionally enabling Draft mode on a Route ie. when previewing a draft record.

### PublishedMiddleware

Similar to `DraftMiddleware` but for Published Records. Useful if your config sets the default mode to
`RevisorContext::Draft`  



