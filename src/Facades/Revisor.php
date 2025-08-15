<?php

declare(strict_types=1);

namespace Indra\Revisor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Indra\Revisor\Revisor
 *
 * @method static void createTableSchemas(string $baseTableName, \Closure $callback, \Illuminate\Database\Eloquent\Model|string|null $model = null)
 * @method static void alterTableSchemas(string $baseTableName, \Closure $callback)
 * @method static void dropTableSchemasIfExists(string $baseTableName)
 * @method static string getVersionTableFor(string $baseTableName)
 * @method static string getPublishedTableFor(string $baseTableName)
 * @method static string getDraftTableFor(string $baseTableName)
 * @method static string getSuffixedTableNameFor(string $baseTableName, ?\Indra\Revisor\Enums\RevisorContext $context = null)
 * @method static \Illuminate\Support\Collection getAllTablesFor(string $baseTableName)
 * @method static null|\Indra\Revisor\Enums\RevisorContext getContext(bool $orDefaultContext = true)
 * @method static \Indra\Revisor\Revisor setContext(\Indra\Revisor\Enums\RevisorContext $context)
 * @method static \Indra\Revisor\Revisor draftContext()
 * @method static \Indra\Revisor\Revisor publishedContext()
 * @method static \Indra\Revisor\Revisor versionContext()
 * @method static mixed withContext(\Indra\Revisor\Enums\RevisorContext $context, callable $callback)
 * @method static mixed withPublishedContext(callable $callback)
 * @method static mixed withVersionContext(callable $callback)
 * @method static mixed withDraftContext(callable $callback)
 */
class Revisor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Indra\Revisor\Revisor::class;
    }
}
