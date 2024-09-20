<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Indra\Revisor\Contracts\HasRevisor as HasRevisorContract;
use Indra\Revisor\Enums\RevisorMode;
use Indra\Revisor\Facades\Revisor;

trait HasRevisor
{
    use HasPublishing;
    use HasVersioning;

    protected ?RevisorMode $mode = null;

    public static function bootHasRevisor(): void
    {
        static::deleted(function (HasRevisorContract $model) {
            if ($model->isVersionTableRecord()) {
                $model->handleVersionDeletion();
            }

            if ($model->isDraftTableRecord()) {
                $model->handleDraftDeletion();
            }

            if ($model->isPublishedTableRecord()) {
                $model->handlePublishedDeletion();
            }
        });

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(function (HasRevisorContract $model) {
                if ($model->isVersionTableRecord()) {
                    $model->handleVersionDeletion();
                }

                if ($model->isDraftTableRecord()) {
                    $model->handleDraftDeletion(force: true);
                }

                if ($model->isPublishedTableRecord()) {
                    $model->handlePublishedDeletion();
                }
            });
        }

        if (method_exists(static::class, 'restored')) {
            static::restored(function (HasRevisorContract $model): void {
                if ($model->isDraftTableRecord()) {
                    $model->publishedRecord?->restoreQuietly();
                    $model->versions->restoreQuietly();
                }
            });
        }
    }

    /**
     * Overrides Model::getTable to return the appropriate
     * table (draft, version, published) based on
     * the current RevisorMode
     */
    public function getTable(): string
    {
        return $this->table ?? Revisor::getSuffixedTableNameFor($this->getBaseTable());
    }

    /**
     * Get the base table name for the model
     */
    public function getBaseTable(): string
    {
        return $this->baseTable ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    /**
     * Get the Draft table name for the model
     */
    public function getDraftTable(): string
    {
        return Revisor::getDraftTableFor($this->getBaseTable());
    }

    /**
     * Get a Builder instance for the Draft table
     */
    public static function withDraftTable(): Builder
    {
        $instance = new static;

        return $instance->setTable($instance->getDraftTable())->newQuery();
    }

    /**
     * Check if the model is a Draft table record
     */
    public function isDraftTableRecord(): bool
    {
        return $this->getTable() === $this->getDraftTable();
    }

    /**
     * Handle the deletion of a version record
     * Remove version_number from published and draft records
     */
    public function handleVersionDeletion(): void
    {
        if (! $this->isVersionTableRecord()) {
            return;
        }

        foreach ([$this->getPublishedTable(), $this->getDraftTable()] as $table) {
            DB::table($table)
                ->where('id', $this->record_id)
                ->where('version_number', $this->version_number)
                ->update(['version_number' => null]);
        }
    }

    /**
     * Handle the deletion of a draft record
     * Cascades the deletion to the version and draft records
     * Accounting for forceDeletes
     */
    public function handleDraftDeletion(bool $force = false): void
    {
        if (! $this->isDraftTableRecord()) {
            return;
        }

        $force ?
            $this->publishedRecord?->forceDelete() :
            $this->publishedRecord?->delete();

        $force ?
            $this->versions->each->forceDeleteQuietly() :
            $this->versions->each->deleteQuietly();
    }

    /**
     * Handle the deletion of a published record
     * Ensures the draft and current versions
     * are marked as unpublished
     */
    public function handlePublishedDeletion(): void
    {
        if (! $this->isPublishedTableRecord()) {
            return;
        }

        $this->draftRecord?->unpublish();

        $this->currentVersion?->unpublish();
    }
}
