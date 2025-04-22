<?php

declare(strict_types=1);

namespace Indra\Revisor;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Schema;
use Indra\Revisor\Enums\RevisorContext;

class Revisor
{
    /**
     * Creates 3 tables for the given table name:
     * - {baseTableName}_versions, which holds all the versions of the records
     * - {baseTableName}_live, which holds the published version of the records
     * - {baseTableName}, which holds the base data / drafts of the records
     */
    public function createTableSchemas(string $baseTableName, Closure $callback, Model|string|null $model = null): void
    {
        // create the draft table
        Schema::create(static::getDraftTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorContext::Draft);
            $table->boolean(config('revisor.publishing.table_columns.is_published'))->default(0);
            $table->timestamp(config('revisor.publishing.table_columns.published_at'))->nullable();
            $table->nullableMorphs(config('revisor.publishing.table_columns.publisher'));
            $table->boolean(config('revisor.versioning.table_columns.is_current'))->default(0);
            $table->unsignedInteger(config('revisor.versioning.table_columns.version_number'))->unsigned()->nullable()->index();
        });

        // create the versions table
        Schema::create(static::getVersionTableFor($baseTableName), function (Blueprint $table) use ($callback, $baseTableName, $model) {
            $callback($table, RevisorContext::Version);
            $table->boolean(config('revisor.publishing.table_columns.is_published'))->default(0)->index();
            $table->timestamp(config('revisor.publishing.table_columns.published_at'))->nullable();
            $table->nullableMorphs(config('revisor.publishing.table_columns.publisher'));
            $table->boolean(config('revisor.versioning.table_columns.is_current'))->default(0)->index();
            $table->unsignedInteger(config('revisor.versioning.table_columns.version_number'))->unsigned()->nullable()->index();

            if ($model) {
                // allows for uuid and ulid primary keys
                $table->foreignIdFor($model, config('revisor.versioning.table_columns.record_id'))->constrained(static::getDraftTableFor($baseTableName))->cascadeOnDelete();
            } else {
                $table->foreignId(config('revisor.versioning.table_columns.record_id'))->constrained(static::getDraftTableFor($baseTableName))->cascadeOnDelete();
            }
        });

        // create the published table
        Schema::create(static::getPublishedTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorContext::Published);
            $table->boolean(config('revisor.publishing.table_columns.is_published'))->default(0);
            $table->timestamp(config('revisor.publishing.table_columns.published_at'))->nullable();
            $table->nullableMorphs(config('revisor.publishing.table_columns.publisher'));
            $table->boolean(config('revisor.versioning.table_columns.is_current'))->default(0);
            $table->integer(config('revisor.versioning.table_columns.version_number'))->unsigned()->nullable()->index();
        });
    }

    /**
     * Alters 3 tables for the given baseTableName:
     * - {baseTableName}_versions, which holds all the versions of the records
     * - {baseTableName}_live, which holds the published version of the records
     * - {baseTableName}, which holds the base data / drafts of the records
     */
    public function alterTableSchemas(string $baseTableName, Closure $callback): void
    {
        // alter the versions table
        Schema::table(static::getVersionTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorContext::Version);
        });

        // alter the published table
        Schema::table(static::getPublishedTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorContext::Published);
        });

        // alter the draft table
        Schema::table(static::getDraftTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorContext::Draft);
        });
    }

    /**
     * Schema::dropIfExists() all the tables for the given baseTableName
     */
    public function dropTableSchemasIfExists(string $baseTableName): void
    {
        $this->getAllTablesFor($baseTableName)->each(function ($tableName) {
            Schema::dropIfExists($tableName);
        });
    }

    /**
     * Get the name of the table that holds the versions
     * of the records for the given baseTableName
     */
    public function getVersionTableFor(string $baseTableName): string
    {
        return $this->getSuffixedTableNameFor($baseTableName, RevisorContext::Version);
    }

    /**
     * Get the name of the table that holds the published
     * records for the given baseTableName
     */
    public function getPublishedTableFor(string $baseTableName): string
    {
        return $this->getSuffixedTableNameFor($baseTableName, RevisorContext::Published);
    }

    /**
     * Get the name of the table that holds the draft
     * records for the given baseTableName
     */
    public function getDraftTableFor(string $baseTableName): string
    {
        return $this->getSuffixedTableNameFor($baseTableName, RevisorContext::Draft);
    }

    /**
     * Get the suffixed table name for the given baseTableName
     * and RevisorContext (defaults to the active RevisorContext)
     */
    public function getSuffixedTableNameFor(string $baseTableName, ?RevisorContext $context = null): string
    {
        $context = $context ?? $this->getContext();

        $suffix = config('revisor.table_suffixes.'.$context->value);

        return $suffix ? $baseTableName.$suffix : $baseTableName;
    }

    /**
     * Get all the tables for the given baseTableName
     */
    public function getAllTablesFor(string $baseTableName): Collection
    {
        return collect([
            // Version table should be returned first so it gets dropped first in dropTableSchemasIfExists
            $this->getVersionTableFor($baseTableName),
            $this->getDraftTableFor($baseTableName),
            $this->getPublishedTableFor($baseTableName),
        ]);
    }

    /**
     * Get the current RevisorContext
     */
    public function getContext(bool $orDefaultContext = true): ?RevisorContext
    {
        $value = Context::get(RevisorContext::KEY);

        if ($value) {
            return RevisorContext::from($value);
        }

        return $orDefaultContext ? config('revisor.default_context') : null;
    }

    /**
     * Set the current RevisorContext
     */
    public function setContext(RevisorContext $context): static
    {
        Context::add(RevisorContext::KEY, $context->value);

        return $this;
    }

    /**
     * Set the current RevisorContext to Draft
     */
    public function draftContext(): static
    {
        $this->setContext(RevisorContext::Draft);

        return $this;
    }

    /**
     * Set the current RevisorContext to Published
     */
    public function publishedContext(): static
    {
        $this->setContext(RevisorContext::Published);

        return $this;
    }

    /**
     * Set the current RevisorContext to Version
     */
    public function versionContext(): static
    {
        $this->setContext(RevisorContext::Version);

        return $this;
    }

    /**
     * Execute the given callback with the given RevisorContext
     * Useful for switching context temporarily
     */
    public function withContext(RevisorContext $context, callable $callback): mixed
    {
        $previousContext = $this->getContext(false);

        $this->setContext($context);

        $result = $callback($this);

        $previousContext ?
            $this->setContext($previousContext) :
            Context::forget(RevisorContext::KEY);

        return $result;
    }

    /**
     * Execute the given callback with the Version RevisorContext
     */
    public function withPublishedContext(callable $callback): mixed
    {
        return $this->withContext(RevisorContext::Published, $callback);
    }

    /**
     * Execute the given callback with the Version RevisorContext
     */
    public function withVersionContext(callable $callback): mixed
    {
        return $this->withContext(RevisorContext::Version, $callback);
    }

    /**
     * Execute the given callback with the Draft RevisorContext
     */
    public function withDraftContext(callable $callback): mixed
    {
        return $this->withContext(RevisorContext::Draft, $callback);
    }
}
