# Versioning

The examples below demonstrate various common use cases relating to versioning records. You may wish to review
the [HasVersioning Trait](https://github.com/indracollective/laravel-revisor/blob/main/src/Concerns/HasVersioning.php)
if you'd like to dig deeper.

::: info NOTE
All examples below are in the context of `RevisorMode::Draft` and Revisor-enabled `Page` Model
:::

## Automatic Versioning on Created/Updated

**By Default**, Revisor will create a new Version record whenever a Draft record is created or updated.

```php
$page = Page::create([...]);

echo $page->versions()->count(); // 1

$page->update([...]);

echo $page->versions()->count(); // 2
```

If you would like more control over when new Version records are created, you can disable auto-versioning in your
`config/revisor.php` file:

```php
...
'versioning' => [
    'save_new_version_on_created' => false,
    'save_new_version_on_updated' => false,
]    
...
```

You can also override the default behaviour on a per-model basis by using the `saveNewVersionOnCreated` and
`saveNewVersionOnUpdated` methods which accept a boolean value.

```php
// config.revisor.versioning.save_new_version_on_created = false
// config.revisor.versioning.save_new_version_on_updated = false

$page = Page::make([...]);
$page->saveNewVersionOnCreated(true)->save();

echo $page->versions()->count(); // 1

$page->saveNewVersionOnUpdated(true)->update([...]);

echo $page->versions()->count(); // 2
```

## Manual Versioning

You can manually version records by calling the `saveNewVersion()` method on the Draft record.

```php
$page = Page::create([...]);

echo $page->versions()->count(); // 1

$page->saveNewVersion();

echo $page->versions()->count(); // 2
```

To update the current Version record rather than creating a new Version record, you can call the
`syncToCurrentVersionRecord()` method on the Draft record.

```php
$page->update([...])->syncToCurrentVersionRecord();
```

## Retrieving Version Records

Get all Versions for a Draft or Published record via the `versionRecords` `HasMany` relationship.

```php
$page->versionRecords;
``` 

Get the current Version record for a Draft or Published record via the `currentVersion` `HasOne` relationship.

```php 
$page->currentVersion;
``` 

Get Version records without querying the Draft or Published tables.

```php
Page::withVersionRecords()->where('record_id', 1);
```

## Restore a Previous Version

In the below example, we have a Draft record with two Versions. We want to restore the Draft record to the state of
the first Version. This can be done in a couple of ways depending on your use case and what data you have available in
your context :

```php
$firstVersion = $page->versions()->first();

$page->revertToVersion($firstVersion);

// or 

$page->revertToVersion($firstVersion->id);

// or

$page->revertToVersionNumber($firstVersion->version_number);

// or 

$firstVersion->restoreDraftToThisVersion();
```

## Pruning Versions

**By Default**, Revisor will keep the last 10 Versions for each Draft record.

...

...

## Version Pruning

...




