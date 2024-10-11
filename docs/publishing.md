# Publishing

When a record is Published, the publishing related columns (is_published, published_at etc) are updated on the Draft
record, and the Draft record is inserted into the Published table.

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

To automatically publish records on creation/update, you can make use of the `publishOnCreated` and
`publishOnUpdated` methods. This can be useful in Admin Panels like FilamentPHP where you want to augment the default
save behaviour without having to override the save method.

```php
$page = Page::make([...]);
$page->publishOnCreated();
$page->save(); 

echo $page->isPublished(); // true
```

To automatically publish records on Created/Updated **by default**, you can set the following in your
`config/revisor.php` file:

```php
...
'publishing' => [
    'publish_on_created' => true,
    'publish_on_updated' => true,
]
...
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
Draft record and Version record.

```php
$page = Page::first();
$page->unpublish();

echo $page->isPublished(); // false
```

::: info NOTE
The `unpublish` method must be called on the Draft record. While this may seem counter-intuitive, the reason is to
maintain a one way flow, where the state of Published and Version records are always determined by the "main" Draft
record, effectively making them read-only artifacts of the Draft record.  
:::

