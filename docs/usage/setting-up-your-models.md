# Setting up your Models

Revisor enabled Models require the `HasRevisor` trait and the `HasRevisor` interface.

Additionally, a protected `$baseTable` property can be defined instead of the `$table` property sometimes defined on Eloquent Models. This allows the Model's `$table` property to be defined dynamically by this package, depending on which of the draft/published/versions tables you want to read/write.

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
