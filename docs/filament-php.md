# Revisor + Filament = 🔥

Instantly add robust draft, versioning, and publishing functionality to your FilamentPHP admin panel with the [Revisor Filament plugin](https://github.com/indracollective/laravel-revisor-filament). This plugin offers a collection of Filament Actions, Table Columns, and Page components to seamlessly integrate Revisor with [FilamentPHP](https://filamentphp.com), a popular admin panel for Laravel composed of beautiful full-stack components.

> **Note:** This documentation is for Filament v4. For Filament v3, see the [archived v3 documentation](filament-php-v3.md).

## Screenshots

![List Records](/assets/screenshots/list_records.png){.light-only}
![List Records](/assets/screenshots/list_records_dark.png){.dark-only}

☝️ Table Actions / Bulk Actions for publishing and unpublishing records, viewing the revision history in Filament Tables.

![Edit Records](/assets/screenshots/edit_record.png){.light-only}
![Edit Records](/assets/screenshots/edit_record_dark.png){.dark-only}

☝️ Regular Actions for publishing and unpublishing records, viewing the revision history on Filament Edit pages.

![View Versions](/assets/screenshots/view_version_record.png){.light-only}
![View Versions](/assets/screenshots/view_version_record_dark.png){.dark-only}

☝️ View the version history of a record, and Revert to a previous versions of a record.

## Installation

Follow the [Revisor installation guide](/installation) to get started.

## Examples

### Table Columns

```php
use Filament\Resources\Resource;
use Indra\RevisorFilament\Filament\StatusColumn;
use Indra\RevisorFilament\Filament\PublishInfoColumn;

class PageResource extends Resource
{
    ...

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                StatusColumn::make('status'),
                PublishInfoColumn::make('publish_info'),
            ])
            ...
```

☝️ Add the
`StatusColumn` to your Resource's Table definition to display the records's published statuses (draft, published, revised).

Add the
`PublishInfoColumn` to your Resource's Table definition to display the records's publish information (published date, publisher).

### Table Actions

```php
use Filament\Resources\Resource;
use Filament\Actions\ActionGroup;
use Indra\RevisorFilament\Filament\PublishAction;
use Indra\RevisorFilament\Filament\UnpublishAction;
use Indra\RevisorFilament\Filament\ListVersionsAction;

class PageResource extends Resource
{
    ...

    public static function table(Table $table): Table
    {
        return $table
            ->recordActions([
                ActionGroup::make([
                    PublishAction::make(),
                    UnpublishAction::make(),
                    ListVersionsAction::make(),
                ])
            ])
            ...
    }
```

☝️ Add the
`PublishAction` to your Resource's Table definition to display a Publish Action for each record. This action will only display on records that are
**not published**.

Add the
`UnpublishAction` to your Resource's Table definition to display an Unpublish Action for each record. This action will only display on records that
**are published**.

Add the
`ListVersionsAction` to your Resource's Table definition to allow users to view the version history of a record. Note this this action requires that your Resource has a versions page, see [List Version Records](#list-version-records).

### Table Bulk Actions

```php
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Indra\RevisorFilament\Filament\PublishBulkAction;
use Indra\RevisorFilament\Filament\UnpublishBulkAction;

class PageResource extends Resource
{
    ...

    public static function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    PublishBulkAction::make(),
                    UnpublishBulkAction::make(),
                ])
            ])
            ...
    }
```

☝️ Add the
`PublishBulkAction` and `UnpublishBulkAction` to your Resource's Table definition to display Publish/Unpublish Actions for all selected records.

### List Version Records

```php
use App\Filament\Resources\PageResource;
use App\Models\Model;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VersionRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'versionRecords';

    protected static ?string $relatedResource = PageResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Versions')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('version_number')
                    ->label('Version #'),
                IconColumn::make('is_current')
                    ->label('Current')
                    ->boolean(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
            ])
            ->bulkActions([])
            ->filters([])
            ->recordActions([
                ViewAction::make('view_version')
                    ->url(fn (Model $record): string => PageResource::getUrl('version', [
                        'record' => $record->record_id,
                        'version' => $record->id,
                    ])),
            ])
            ->recordUrl(fn (Model $record): string => PageResource::getUrl('version', [
                'record' => $record->record_id,
                'version' => $record->id,
            ]));
    }
}
```

☝️ To display the version history of a record, create a new Filament RelationManager that extends `RelationManager`. This provides a table showing all versions of a record with actions for viewing specific versions.

```php
use Filament\Resources\Resource;
use App\Filament\Resources\PageResource\RelationManagers\VersionRecordsRelationManager;

class PageResource extends Resource
{
    ...
    
    public static function getRelations(): array
    {
        return [
            VersionRecordsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'version' => ViewPageVersion::route('/{record}/version/{version}'),
        ];
    }
```

☝️ Register your VersionRecordsRelationManager in your Resource's `getRelations()` method and add the version view page to `getPages()`.

### View Version Records

```php
use App\Filament\Resources\PageResource;
use Indra\RevisorFilament\Filament\ViewVersion;

class ViewPageVersion extends ViewVersion
{
    protected static string $resource = PageResource::class;
}
```

☝️ To display a full view of a particular version of a record, create a new Filament Resource Page that extends the
`ViewVersion` Page Class. The ViewVersion Class displays the selected version of your record using your Resource's form or infolist, and provides an action for reverting the record to this version.

```php
use Filament\Resources\Resource;
use App\Filament\Resources\PageResource\Pages\ViewPageVersion;

class PageResource extends Resource
{
    ...
    public static function getPages(): array
    {
        return [
            'version' => ViewPageVersion::route('/{record}/version/{version}'),
        ];
    }
```

☝️ Register your ViewPageVersion Page in your Resource's `getPages()` method. Note the updated route pattern for Filament v4.

