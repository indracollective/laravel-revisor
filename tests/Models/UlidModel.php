<?php

declare(strict_types=1);

namespace Indra\Revisor\Tests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Indra\Revisor\Concerns\HasRevisor;
use Indra\Revisor\Contracts\HasRevisor as HasRevisorContract;

class UlidModel extends Model implements HasRevisorContract
{
    use HasRevisor;
    use HasUlids;

    protected string $baseTable = 'ulid_models';

    protected $fillable = [
        'title',
    ];
}
