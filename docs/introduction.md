# Introduction

**Laravel Revisor provides robust draft, publishing and versioning for Laravel Eloquent Models.**

There are a few different ways to approach versioning and publishing in Laravel, and at least as many existing packages that suit different use cases.

Let's introduce Revisor's approach and see if it aligns with your projects / requirements.

### Design Goals

### 1. Draft, Published and Versioned records are first class citizens in your database

Revisor provides everything you need to seamlessly manage your Draft, Published and Version records in separate, complete tables for each Model.

Records in any of these 3 states are immediately instantiable as Eloquent Models.

```php
// Example Revisor Migration
// Creates 3 tables: pages_published, pages_drafts and pages_versions
// Complete with versioning/publishing metadata columns

public function up(): void
{
    Revisor::createTableSchemas('pages', function (Blueprint $table) {
        $table->id();
        $table->string('title');
    });
}
```

This allows for a clear separation of concerns and pushes the complexity of managing various states of your records further down the stack.

### 2. Context Management is intuitive and flexible

Context Management refers the ability to switch between Draft, Published and Version states of your data.

```php
Revisor::withDraftContext(function() {
    // all draft pages
    $pages = Page::all();

    // all published pages
    $publishedPages = Page::withPubslishedContext()->all();
});
```

Revisor allows you to switch contexts at any level: from Global Config, Middleware, context-isolating Closures and Query Builder. Precedence is given to the most specific context. Revisor allows you to switch contexts at any level: from Global Config, Middleware, context-isolating Closures and Query Builder. Precedence is given to the most specific context.

Under the hood, the active
`RevisorContext` determines which table will be read/written when interacting with your Eloquent Models.

### 3. Publishing & versioning procedures are simple and easy to follow

Revisor ensures versioning and publishing procedures are uni-directional. Use of Global Scopes and Model Events are kept to a minimum.

For visibility, the active
`RevisorContext` is registered with [Laravel's Context Store](https://laravel.com/docs/context), ensuring it is visible in logs and error pages.

### 4. Easy integration with popular admin panels

The [Revisor Filament plugin](https://github.com/indracollective/laravel-revisor-filament) offers a collection of Filament Actions, Table Columns, and Page components to seamlessly integrate Revisor with [FilamentPHP](https://filamentphp.com), a popular admin panel for Laravel composed of beautiful full-stack components.

[Laravel Nova](https://nova.laravel.com/) support is planned for a future release.

---

&nbsp;

If we're aligned with your requirements so far, great! Let's dive in and publish/version all the things.
