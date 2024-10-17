# Publishing

When a record is Published, the [publishing related columns](/preparing-your-models#revisor-table-columns) are updated on the Draft
record, and the Draft record is copied into the Published table.

The examples below demonstrate various common use cases relating to publishing records. You may wish to review
the [HasPublishing Trait](https://github.com/indracollective/laravel-revisor/blob/main/src/Concerns/HasPublishing.php)
if you'd like to dig deeper.

::: info NOTE
All examples below are in context of `RevisorContext::Draft` and a Revisor-enabled `Page` Model
:::

## Publish a Record

```php
$page = Page::create([...]);
$page->publish();

echo $page->isPublished(); // true
```

## Automatic Publishing on Created/Updated

By default, Revisor will NOT Publish a record when a Draft is created or updated.

To automatically publish records on Created/Updated, you can set the following in your
`config/revisor.php` file:

```php
...
'publishing' => [
    'publish_on_created' => true,
    'publish_on_updated' => true,
]
...
```

You can also override the default behaviour on a per-Model basis by using the `publishOnCreated` and `publishOnUpdated` methods which accept a boolean value.

```php
$page = Page::make([...]);
$page->publishOnCreated(true);
$page->save(); 

echo $page->isPublished(); // true
```



## Retrieve the Published Record

Revisor-enabled Models have a `publishedRecord` HasOne relationship, which allows you to retrieve the published record
from the Draft record or one of its Version records.

```php
$page = Page::first();
$publishedPage = $page->publishedRecord;

// the Draft record can also be retrieved from the Published record

$pageDraft = $page->draftRecord; 
```

## Check Published Status

Check the published status of a record using the `isPublished` method on the Draft record.

If your Draft record has been updated since it was last published, the `isPublished` method will still return true.

Use the `isRevised` method to check if the Draft record has been updated since it was last published.

```php
$page = Page::create([...])->publish();
$page->update([...])

echo $page->isPublished(); // true
echo $page->isRevised(); // true

$page->publish();

echo $page->isRevised(); // false
```

## Unpublish a Record

Unpublishing a record deletes the record from its Published table, and sets the `is_published` column to false on the
Draft record and current Version record.

```php
$page = Page::first();
$page->unpublish();

echo $page->isPublished(); // false
```

::: info NOTE
The `unpublish` method must be called on the Draft record. While this may seem counter-intuitive, the reason is to
maintain a one way flow, where the state of Published and Version records are always determined by the "main" Draft
record, effectively making them read-only artifacts of the state of, and actions performed on the Draft record.  
:::

