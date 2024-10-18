# Introduction

**Laravel Revisor provides robust draft, publishing and versioning for Laravel Eloquent Models.**

### Design Goals

### 1. Draft, Published and Versioned records are first class citizens of your database and immediately instantiable as Eloquent Models.

Revisor provides everything you need to seamlessly manage your Draft, Published and Version records in separate, complete tables for each Model.

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

This allows for a clear separation of concerns and pushes the complexity of managing various states of your records  further down the stack.

### 2. Context Management should be intuitive and powerful enough to handle any scenario

Context Management refers the ability to switch between Draft, Published and Version states of your data.

```php
Revisor::withDraftContext(function() {
    // all draft pages
    $pages = Page::all();

    // all published pages
    $publishedPages = Page::withPubslishedContext()->all();
});
```

Revisor allows you to switch contexts at any level you will need: from Global Config, Middleware, context-isolating Closures and Query Builder. Precedence is given to the most specific context.

Under the hood, the active `RevisorContext` determines which table will be read/written when interacting with your Eloquent Models.

### 3. Simple and easy to follow

Revisor ensures versioning and publishing procedures are uni-directional. Use of Global Scopes and Model Events are kept to a minimum.

The active `RevisorContext` is registered with [Laravel's Context Store](https://laravel.com/docs/context), ensuring it is visible in logs and error pages.

---

&nbsp;

If we're aligned with your requirements so far, great! Let's dive in and publish/version all the things.
