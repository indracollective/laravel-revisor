<?php

declare(strict_types=1);

namespace Indra\Revisor\Tests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Indra\Revisor\Concerns\HasRevisor;
use Indra\Revisor\Contracts\HasRevisor as HasRevisorContract;

class HiddenIdModel extends Model implements HasRevisorContract
{
    use HasRevisor;

    protected string $baseTable = 'hidden_id_models';

    protected $fillable = [
        'slug',
    ];

    protected $hidden = [
        'id'
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
