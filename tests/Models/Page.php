<?php

declare(strict_types=1);

namespace Indra\Revisor\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Indra\Revisor\Concerns\HasRevisor;
use Indra\Revisor\Contracts\HasPublishing as HasPublishingContract;
use Indra\Revisor\Contracts\HasVersioning as HasVersioningContract;

class Page extends Model implements HasPublishingContract, HasVersioningContract
{
    use HasRevisor;

    protected $fillable = [
        'title',
        'description',
    ];
}
