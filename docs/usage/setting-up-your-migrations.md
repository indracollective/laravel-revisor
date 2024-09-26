# Setting up your Migrations

Revisor operates on 3 tables (draft, published, versions) per model and provides convenient methods for applying and synchronising migrations across these tables.

### **Creating New Tables**

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

The `Revisor::createTableSchemas` will use the `baseTable` given as the first argument to create all 3 `page_drafts`, `page_versions` and `page_published` tables. As with regular Laravel migrations, the callback passed in the second argument will be used to build the table schemas according to your needs.

### Additional Revisor Table Columns

Revisor will also add the following extra columns to your tables:

| Column          | Type            | Purpose                                            |
| --------------- | --------------- | -------------------------------------------------- |
| publisher       | nullableMorphs  | User who published the record                      |
| published\_at   | timestamp       | When the record was published                      |
| is\_published   | boolean         | Whether the record is published                    |
| is\_current     | boolean         | Whether the record is the current version          |
| version\_number | unsignedInteger | Sequential version number                          |
| record\_id      | foreignKey      | id of draft/published record (versions table only) |

### **Amending Existing Tables**

Amending/modifying existing Revisor table schemas can be done in much the same way as creating new ones, by using the `amendTableSchemas` method on the Revisor Facade:

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
