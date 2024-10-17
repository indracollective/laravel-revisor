# Introduction

Laravel Revisor provides robust draft, publishing and versioning for Laravel Eloquent Models.

There are a good handful of Laravel versioning packages out there with varying approaches. Revisor aims to overcome their different trade-offs, offering maximum power, flexibility and interoperability with minimum exposure to complexity.

## Design Goals

### 1. Draft, Published and Versioned record data should be clearly distinguished and cleanly instantiable as Eloquent Models.

Revisor provides everything you need to seamlessly manage your Draft, Published and Version records in separate, complete tables for each Model. 

For example, a `Page` model would have 3 tables: `pages_published`, `pages_drafts` and `pages_versions` 

This allows for a clear separation of concerns and reduces exposure to the complexity inherent in managing multiple versions of records.

```php
// Example: A Revisor migration creates/alters 3 tables for each Model
public function up(): void
{
    Revisor::createTableSchemas('pages', function (Blueprint $table) {
        $table->id();
        $table->string('title');
    });  
}
```

### 2. Context Management should be intuitive and powerful enough to handle any scenario

Context Management refers the ability to switch between Draft, Published and Version states of your data.

Revisor allows you to switch contexts at any level you will need: from Global Config, Middleware, context-isolating Closures and Query Builder. Precedence is given to the most specific context.    

Under the hood, the active `RevisorContext` determines which table will be read/written when interacting with your Eloquent Models.

```php
Revisor::withDraftContext(function() {
    // all draft pages
    $pages = Page::all(); 
    
    // all published pages
    $publishedPages = Page::withPubslishedContext()->all();
});
```

### 3. Simple and easy to follow

Revisor ensures versioning and publishing procedures are uni-directional. Use of Global Scopes and Model Events are kept to a minimum. 

The active `RevisorContext` is registered with [Laravel's Context Store](https://laravel.com/docs/context), ensuring it is visible in logs and error pages.

---

&nbsp;

If all that aligns with your requirements, great! Let's dive in and publish/version all the things!
