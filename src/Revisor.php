<?php

namespace Indra\Revisor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Revisor
{
    /**
     * Creates 3 tables for the given table name:
     * - {table}_versions, which holds all the versions of the records
     * - {table}_live, which holds the published version of the records
     * - {table}, which holds the base data / drafts of the records
     **/
    public function schemaCreate(string $table, \Closure $callback): void
    {
        $baseTableName = $table;

        // create the versions table
        Schema::create(static::getVersionsTableNameFor($table), function (Blueprint $table) use ($callback, $baseTableName) {
            $callback($table);
            $table->nullableMorphs('publisher');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_published')->default(false);
            $table->foreignId('record_id')->constrained($baseTableName)->cascadeOnDelete();
        });

        // create the live table
        Schema::create(static::getPublishedTableNameFor($table), function (Blueprint $table) use ($callback) {
            $callback($table);
            $table->nullableMorphs('publisher');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_published')->default(false);
        });

        // create the base table
        Schema::create($table, function (Blueprint $table) use ($callback) {
            $callback($table);
            $table->nullableMorphs('publisher');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_published')->default(false);
        });
    }

    public static function getVersionsTableNameFor(string $table): string
    {
        return str($table)->singular().'_versions';
    }

    public static function getPublishedTableNameFor(string $table): string
    {
        return $table.'_live';
    }
}
