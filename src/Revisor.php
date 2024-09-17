<?php

declare(strict_types=1);

namespace Indra\Revisor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class Revisor
{
    /**
     * Creates 3 tables for the given table name:
     * - {baseTableName}_versions, which holds all the versions of the records
     * - {baseTableName}_live, which holds the published version of the records
     * - {baseTableName}, which holds the base data / drafts of the records
     **/
    public function createTableSchemas(string $baseTableName, \Closure $callback): void
    {
        // create the versions table
        Schema::create(static::getVersionTableFor($baseTableName), function (Blueprint $table) use ($callback, $baseTableName) {
            $callback($table);
            $table->nullableMorphs('publisher');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_current')->default(0)->index();
            $table->boolean('is_published')->default(0)->index();
            $table->integer('version_number')->unsigned()->nullable()->index();
            $table->foreignId('record_id')->constrained($baseTableName)->cascadeOnDelete();
        });

        // create the live table
        Schema::create(static::getPublishedTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table);
            $table->nullableMorphs('publisher');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_current')->default(0);
            $table->boolean('is_published')->default(0);
            $table->integer('version_number')->unsigned()->nullable()->index();
        });

        // create the base table
        Schema::create($baseTableName, function (Blueprint $table) use ($callback) {
            $callback($table);
            $table->nullableMorphs('publisher');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_current')->default(0);
            $table->boolean('is_published')->default(0);
            $table->integer('version_number')->unsigned()->nullable()->index();
        });
    }

    /**
     * Amends 3 tables for the given baseTableName:
     * - {baseTableName}_versions, which holds all the versions of the records
     * - {baseTableName}_live, which holds the published version of the records
     * - {baseTableName}, which holds the base data / drafts of the records
     **/
    public function amendTableSchemas(string $baseTableName, \Closure $callback): void
    {
        // amend the versions table
        Schema::table(static::getVersionTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table);
        });

        // amend the live table
        Schema::table(static::getPublishedTableFor($baseTableName), function (Blueprint $table) use ($callback) {
            $callback($table);
        });

        // amend the base table
        Schema::table($baseTableName, function (Blueprint $table) use ($callback) {
            $callback($table);
        });
    }

    /**
     * Get the name of the table that holds the versions
     * of the records for the given baseTableName
     **/
    public static function getVersionTableFor(string $baseTableName): string
    {
        return str($baseTableName)->singular().'_versions';
    }

    /**
     * Get the name of the table that holds the published versions
     * of the records for the given baseTableName
     **/
    public static function getPublishedTableFor(string $baseTableName): string
    {
        return $baseTableName.'_published';
    }

    public static function getAllTablesFor(string $baseTableName): Collection
    {
        return collect([
            $baseTableName,
            static::getPublishedTableFor($baseTableName),
            static::getVersionTableFor($baseTableName),
        ]);
    }
}
