# Preparing Migrations & Models

Revisor can be added to your Models in two simple steps:&#x20;

1. [Prepare Database Migrations](#_1-prepare-database-migrations)
2. [Prepare Models](#_2-prepare-models)

## 1. Prepare Database Migrations

Revisor operates on 3 tables (draft, published, versions) per Model. Fear not! Revisor makes managing migrations for
these just as easy as standard migrations.&#x20;

Let's generate a new migration and take a look...

```bash
php artisan make:migration
```

### Generating New Revisor Tables

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

Run you migration as usual with:

```bash
php artisan migrate
```

When the migration runs, `Revisor::createTableSchemas` will use the `baseTable` "pages" given as the first argument to
create all 3 `pages_drafts`,
`pages_versions` and `pages_published` tables. As with regular Laravel migrations, the closure passed in the second
argument will be used to build the table schemas according to your needs.

::: tip Using UUID / ULID Primary Keys?
If your model has `Ulid` or `Uuid` primary keys, you will need to pass a third argument to `createTableSchemas` to specify the model class. This is necessary for Revisor to correctly handle the primary key.
:::

### Revisor Table Columns

Revisor's `createTableSchemas` method will add the following extra columns to your tables:

| Column          | Type            | Purpose                                            |
|-----------------|-----------------|----------------------------------------------------|
| publisher       | nullableMorphs  | User who published the record                      |
| published\_at   | timestamp       | When the record was published                      |
| is\_published   | boolean         | Whether the record is published                    |
| is\_current     | boolean         | Whether the record is the current version          |
| version\_number | unsignedInteger | Sequential version number                          |
| record\_id      | foreignKey      | id of draft/published record (versions table only) |

### Altering Existing Revisor Tables

Modifying existing Revisor table schemas can be done in much the same way as creating new ones. This time we'll call
the `alterTableSchemas` method on the `Revisor` Facade:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Indra\Revisor\Facades\Revisor;

return new class extends Migration
{
    public function up(): void
    {
        Revisor::alterTableSchemas('pages', function (Blueprint $table) {
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

### Retrofitting Existing Models/Tables

If you are needing to add Revisor to Models in your application that already have production data stored, we recommend
following the steps in [#generating-new-revisor-tables](preparing-your-models#generating-new-revisor-tables "mention"),
and then
importing the data from the old single table into the new `Draft` and `Published` tables.

## 2. Prepare Models

Adding Revisor to your Models is as simple as implementing the `HasRevisor` Interface and Trait.

This should leave your Model looking something like this:

```php
use Illuminate\Database\Eloquent\Model;
use Indra\Revisor\Concerns\HasRevisor;
use Indra\Revisor\Contracts\HasRevisor as HasRevisorContract;

class Page extends Model implements HasRevisorContract
{
    use HasRevisor;

    protected string $baseTable = 'pages';

    ...
```

::: tip Note
You can define a `$baseTable` property in place of the usual `$table` property, for
cases where your desired table name is not what Laravel would otherwise assume based on your Model class name.
:::
