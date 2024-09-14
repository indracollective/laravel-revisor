<?php

declare(strict_types=1);

namespace Indra\Revisor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Indra\Revisor\Revisor
 */
class Revisor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Indra\Revisor\Revisor::class;
    }
}
