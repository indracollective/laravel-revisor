<?php

declare(strict_types=1);

namespace Indra\Revisor\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Indra\Revisor\Concerns\HasRevisor;
use Indra\Revisor\Contracts\HasRevisor as HasRevisorContract;

class Page extends Model implements HasRevisorContract
{
    use HasRevisor;

    protected string $baseTable = 'pages';

    protected $fillable = [
        'title',
        'description',
    ];
}
