# Laravel Revisor

## Getting Started

### Introduction

Laravel Revisor aims to provide the maximum power and flexibility possible in versioned record management, while
maintaining a very low tolerance for complexity. To achieve this, it offers:

✅ Separate, complete database tables for draft, published and version history records of each Model

✅ Migration API for easily creating/modifying draft, published and version history tables

✅ Easy context management for setting the appropriate reading/writing "mode" at all levels of operation, from global
config, to middleware, mode callbacks and query builder.

✅ Clean, flexible API for drafting, publishing and version management

✅ High configurability and excellent documentation

### Concepts

#### Modes

...

#### Tables

...

#### Base table

### Installation

Install the package via composer:

```bash
composer require indra/laravel-revisor
```

### Configuration

Publish the package configuration to your application:

```bash
php artisan vendor:publish --tag="laravel-revisor-config"
```

The following configurations will then be available in you app in config/revisor.php

```php
return [
    // The default mode determines which table will be read/written to by default
    // The RevisorMode enum is used to define the possible values for this
    // which are `Draft`, `Version` and `Published`
    'default_mode' => RevisorMode::Published,

    // The table suffixes are used to define the table names for each mode
    // The keys are the values of the RevisorMode enum
    // The values are the table suffixes
    'table_suffixes' => [
        RevisorMode::Draft->value => '_drafts',
        RevisorMode::Version->value => '_versions',
        RevisorMode::Published->value => '_published',
    ],

    // The publishing config is used to determine the default publishing behaviour,
    'publishing' => [
        // If true, records will be automatically published on created
        'publish_on_created' => false,
        // If true, records will be automatically published on updated
        'publish_on_updated' => false,
    ],

    // The publishing config is used to determine the default versioning behaviour,
    'versioning' => [
        // If true, new version records will be automatically created when drafts are created
        'save_new_version_on_created' => true,
        // If true, new version records will be automatically created when drafts are updated
        'save_new_version_on_updated' => true,
        // The maximum number of versions to keep
        // if set to true, version records will not be pruned
        'keep_versions' => 10,
    ],
];
```

## Usage

### 1. Write your Migrations

Revisor operates on 3 tables (draft, published, versions) per model and provides convenient methods for applying and
synchronising migrations across these tables.

#### Creating New Tables

Below is an example migration that creates database tables for a revisor-enabled `Page` model

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Indra\Revisor\Facades\Revisor;

return new class extends Migration
{
    public function up(): void
    {
        Revisor::createTableSchemas('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });  
    }
}
```

The `Revisor::createTableSchemas` will use the `baseTableName` given as the first argument to create all 3
`page_drafts`, `page_versions` and `page_published` tables. As with regular Laravel migrations, the callback passed in
the second argument will be used to build the table schemas according to your needs.

Revisor will also add the following extra columns to your tables for as follows:

| Column         | Type            | Purpose                                            |
|----------------|-----------------|----------------------------------------------------|
| publisher      | nullableMorphs  | User who published the record                      |
| published_at   | timestamp       | When the record was published                      |
| is_published   | boolean         | Whether the record is published                    |
| is_current     | boolean         | Whether the record is the current version          |
| version_number | unsignedInteger | Sequential version number                          |
| record_id      | foreignKey      | id of draft/published record (versions table only) |

#### Amending Existing Tables

Amending/modifying table schemas can be done in much the same way as creating new ones, by using the `amendTableSchemas`
method on the Revisor Facade:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Indra\Revisor\Facades\Revisor;

return new class extends Migration
{
    public function up(): void
    {
        Revisor::amendTableSchemas('pages', function (Blueprint $table) {
            $table->string('heading')->change();
            $table->text('content')->nullable();
        });  
    }
}
```

Run you migrations as usual with:

```bash
php artisan migrate
```

Review the generated database schema in your favourite UI to familiarise yourself.

### 2. Set up your Models

Revisor enabled Models require the `HasRevisor` trait and the `HasRevisor` interface.

Additionally, a protected `$baseTable` property can be defined in place of the optional `$table` property sometimes
defined on Eloquent Models. This allows the Model's `$table` property to be defined dynamically by this package,
depending on which of the draft/published/versions tables you want to read/write.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Indra\Revisor\Concerns\HasRevisor;
use Indra\Revisor\Contracts\HasRevisor as HasRevisorContract;

class Page extends Model implements HasRevisorContract
{
    use HasRevisor;

    protected string $baseTable = 'pages';

    ...
```

### Modes

#### About Modes

#### Global Config

#### Middleware

### Interacting with Revisor Records

#### Creating a draft

#### Publishing

##### Publishing

##### Unpublishing

      - Hooks
    - Versioning
      - Creating versions
      - Updating versions
      - Reverting to versions
      - Pruning versions
      - Hooks__
