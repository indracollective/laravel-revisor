# Setup Migrations & Models

Revisor can be added to your models in two simple steps:&#x20;

1. [Create a database migration](setting-up-your-migrations.md#id-1.-migrations-for-revisor-models)&#x20;
2. [Enable Revisor on your Model](setting-up-your-migrations.md#id-2.-enable-revisor-on-your-model)

## 1. Migrations for Revisor Models

Revisor operates on 3 tables (draft, published, versions) per Model. Fear not! Revisor makes managing migrations for these just as easy as standard migrations.&#x20;

Let's generate a new migration and take a look...

```bash
php artisan make:migration
```

### **Generating New Revisor Tables**

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

The `Revisor::createTableSchemas` will use the `baseTable` given as the first argument to create all 3 `pages_drafts`, `pages_versions` and `pages_published` tables. As with regular Laravel migrations, the closure passed in the second argument will be used to build the table schemas according to your needs.

#### Additional Revisor Table Columns

Revisor will also add the following extra columns to your tables:

| Column          | Type            | Purpose                                            |
| --------------- | --------------- | -------------------------------------------------- |
| publisher       | nullableMorphs  | User who published the record                      |
| published\_at   | timestamp       | When the record was published                      |
| is\_published   | boolean         | Whether the record is published                    |
| is\_current     | boolean         | Whether the record is the current version          |
| version\_number | unsignedInteger | Sequential version number                          |
| record\_id      | foreignKey      | id of draft/published record (versions table only) |

### **Amending Existing Revisor Tables**

Amending/modifying existing Revisor table schemas can be done in much the same way as creating new ones. This time we'll  the `amendTableSchemas` method on the `Revisor` Facade:

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

Review the generated database schema in your favourite UI to familiarise yourself with the Revisor database schema.

### Retrofitting Existing Models/Tables

If you are needing to add Revisor to Models in your application that already have production data stored, we recommend following the steps in [#generating-new-revisor-tables](setting-up-your-migrations.md#generating-new-revisor-tables "mention"), and then importing the data from the old single table into the new `Draft` and `Published` tables.

## 2. Enable Revisor on your Model&#x20;

Enabling Revisor on your Model involves 3 simple steps:

1. Implement the `Indra\Revisor\Contracts\HasRevisor` Interface
2. Use the `Indra\Revisor\Concerns\HasRevisor` Trait
3. Define the [`$baseTable`](#user-content-fn-1)[^1]  property (optional)&#x20;

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



[^1]: The `$baseTable` property is available in place of the `$table` property, for cases where your desired table name is not what Laravel would otherwise assume based on your Model class name.
