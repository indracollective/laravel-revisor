<?php

declare(strict_types=1);

namespace Indra\Revisor;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Indra\Revisor\Enums\RevisorMode;

class Revisor
{
    protected ?RevisorMode $mode = null;

    /**
     * Creates 3 tables for the given table name:
     * - {baseTableName}_versions, which holds all the versions of the records
     * - {baseTableName}_live, which holds the published version of the records
     * - {baseTableName}, which holds the base data / drafts of the records
     */
    public function createTableSchemas(string $baseTableName, Closure $callback): void
    {
        // create the draft table
        Schema::create(static::getDraftTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorMode::Draft);
            $table->boolean(config('revisor.publishing.table_columns.is_published'))->default(0);
            $table->timestamp(config('revisor.publishing.table_columns.published_at'))->nullable();
            $table->nullableMorphs(config('revisor.publishing.table_columns.publisher'));
            $table->boolean(config('revisor.versioning.table_columns.is_current'))->default(0);
            $table->unsignedInteger(config('revisor.versioning.table_columns.version_number'))->unsigned()->nullable()->index();
        });

        // create the versions table
        Schema::create(static::getVersionTableFor($baseTableName), function (Blueprint $table) use ($callback, $baseTableName) {
            $callback($table, RevisorMode::Version);
            $table->boolean(config('revisor.publishing.table_columns.is_published'))->default(0)->index();
            $table->timestamp(config('revisor.publishing.table_columns.published_at'))->nullable();
            $table->nullableMorphs(config('revisor.publishing.table_columns.publisher'));
            $table->boolean(config('revisor.versioning.table_columns.is_current'))->default(0)->index();
            $table->unsignedInteger(config('revisor.versioning.table_columns.version_number'))->unsigned()->nullable()->index();
            $table->foreignId(config('revisor.versioning.table_columns.record_id'))->constrained(static::getDraftTableFor($baseTableName))->cascadeOnDelete();
        });

        // create the published table
        Schema::create(static::getPublishedTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorMode::Published);
            $table->boolean(config('revisor.publishing.table_columns.is_published'))->default(0);
            $table->timestamp(config('revisor.publishing.table_columns.published_at'))->nullable();
            $table->nullableMorphs(config('revisor.publishing.table_columns.publisher'));
            $table->boolean(config('revisor.versioning.table_columns.is_current'))->default(0);
            $table->integer(config('revisor.versioning.table_columns.version_number'))->unsigned()->nullable()->index();
        });
    }

    /**
     * Amends 3 tables for the given baseTableName:
     * - {baseTableName}_versions, which holds all the versions of the records
     * - {baseTableName}_live, which holds the published version of the records
     * - {baseTableName}, which holds the base data / drafts of the records
     */
    public function amendTableSchemas(string $baseTableName, Closure $callback): void
    {
        // amend the versions table
        Schema::table(static::getVersionTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorMode::Version);
        });

        // amend the published table
        Schema::table(static::getPublishedTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorMode::Published);
        });

        // amend the draft table
        Schema::table(static::getDraftTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table, RevisorMode::Draft);
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
        return $this->getSuffixedTableNameFor($baseTableName, RevisorMode::Version);
    }

    /**
     * Get the name of the table that holds the published
     * records for the given baseTableName
     */
    public function getPublishedTableFor(string $baseTableName): string
    {
        return $this->getSuffixedTableNameFor($baseTableName, RevisorMode::Published);
    }

    /**
     * Get the name of the table that holds the draft
     * records for the given baseTableName
     */
    public function getDraftTableFor(string $baseTableName): string
    {
        return $this->getSuffixedTableNameFor($baseTableName, RevisorMode::Draft);
    }

    /**
     * Get the suffixed table name for the given baseTableName
     * and RevisorMode (defaults to the current mode)
     */
    public function getSuffixedTableNameFor(string $baseTableName, ?RevisorMode $mode = null): string
    {
        $mode = $mode ?? $this->getMode();

        $suffix = config('revisor.table_suffixes.'.$mode->value);

        return $suffix ? $baseTableName.$suffix : $baseTableName;
    }

    /**
     * Get all the tables for the given baseTableName
     */
    public function getAllTablesFor(string $baseTableName): Collection
    {
        return collect([
            $this->getDraftTableFor($baseTableName),
            $this->getPublishedTableFor($baseTableName),
            $this->getVersionTableFor($baseTableName),
        ]);
    }

    /**
     * Get the current RevisorMode
     */
    public function getMode(): RevisorMode
    {
        return $this->mode ?? config('revisor.default_mode');
    }

    /**
     * Set the current RevisorMode
     */
    public function setMode(RevisorMode $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Execute the given callback with the given RevisorMode
     * Useful for switching modes temporarily
     */
    public function withMode(RevisorMode $mode, callable $callback): mixed
    {
        $previousMode = $this->mode;
        $this->mode = $mode;

        $result = $callback($this);

        $this->mode = $previousMode;

        return $result;
    }

    /**
     * Execute the given callback with the Version RevisorMode
     */
    public function withPublishedRecords(callable $callback): mixed
    {
        return $this->withMode(RevisorMode::Published, $callback);
    }

    /**
     * Execute the given callback with the Version RevisorMode
     */
    public function withVersionRecords(callable $callback): mixed
    {
        return $this->withMode(RevisorMode::Version, $callback);
    }

    /**
     * Execute the given callback with the Draft RevisorMode
     */
    public function withDraftRecords(callable $callback): mixed
    {
        return $this->withMode(RevisorMode::Draft, $callback);
    }
}
