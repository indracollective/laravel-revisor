# Modes & Dynamic Tables

When you read or write one of your Revisor Models through Eloquent, Revisor will evaluate which table to use (draft,
published or versions) by checking relevant details of your application state.&#x20;

### Common Use Case - Global Config + Middleware

In a common use case, we have an application/website that displays published records on the public site, and draft
records in the admin panel.\
\
Displaying published records on the public site can be achieved by simply ensuring the config setting
`revisor.default_context` is set to `RevisorContext::Published`, which is the case by default.

<pre class="language-php"><code class="lang-php">config()->set('revisor.default_context', RevisorContext::Published);

// returns all records from the page_published table.
<strong>Page::all() 
</strong></code></pre>

For the admin panel, we can apply the `DraftMiddleware` which overrides the default config on any routes it is applied
to.

```php
use Indra\Revisor\Middleware\DraftMiddleware;

Route::get('/admin')->middleware([DraftMiddleware::class]);
```

### Overriding Default Config & Middleware - examples&#x20;

```php
use Indra\Revisor\Facades\Revisor;

config()->set('revisor.default_context', RevisorContext::Published);

// override by changing the mode
Revisor::setContext(RevisorContext::Draft);

Page::all(); // returns draft records

// return mode to previous mode
Revisor::setContext(RevisorContext::Published);

// Revisor::withContext is better if you want to 
// perform your desired mode operation in isoloation
// without having to track / reset to the previous mode
Revisor::withContext(RevisorContext::Draft, function() {
    Page::all(); // returns records from the draft table
});

// If you you need even more isolation, you can use withTable on your Model

Page::withDraftContext()->all(); // query builder for draft records
Page::withPublishedContext()->all() // query builder for published records
Page::withVersionsTable()->all() // query builder for versions records
```

