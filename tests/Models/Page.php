<?php

declare(strict_types=1);

namespace Indra\Revisor\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Indra\Revisor\Concerns\HasRevisor;
use Indra\Revisor\Contracts\RevisorContract;

class Page extends Model implements RevisorContract
{
    use HasRevisor;

    protected $fillable = [
        'title',
        'description',
    ];
}
