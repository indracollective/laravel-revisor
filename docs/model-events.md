# Model Events

Laravel Revisor fires several Model Events for you hook into key publishing and versioning events.

All events are fired on the Draft record, and pass the relevant Published or Versioned record as an argument to your
callback (if applicable).

Event listeners can be registered using static methods on the Model class. The method names correspond to the event name.

```php
Page::published(function (Page $page, Page $publishedRecord) {
    ...
});
```

## Publishing Events

| Event        | Description                       |
|--------------|-----------------------------------|
| publishing   | Fired before publishing a Model   |
| published    | Fired after publishing a Model    |
| unpublishing | Fired before unpublishing a Model |
| unpublished  | Fired after unpublishing a Model  |

## Versioning Events

| Event                   | Description                                 |
|-------------------------|---------------------------------------------|
| savingNewVersion        | Fired before saving a new Version           |
| savedNewVersion         | Fired after saving a new Version            |
| syncingToCurrentVersion | Fired before syncing to the current Version |
| syncedToCurrentVersion  | Fired after syncing to the current Version  |
| revertingToVersion      | Fired before reverting to a Version         |
| revertedToVersion       | Fired after reverting to a Version          |
