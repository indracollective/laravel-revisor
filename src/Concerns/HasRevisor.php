<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Indra\Revisor\Contracts\HasRevisor as HasRevisorContract;
use Indra\Revisor\Enums\RevisorContext;
use Indra\Revisor\Facades\Revisor;

trait HasRevisor
{
    use HasPublishing;
    use HasVersioning;

    protected ?RevisorContext $revisorContext = null;

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
                    $model->versionRecords->restoreQuietly();
                }
            });
        }
    }

    public function newInstance($attributes = [], $exists = false): self
    {
        return parent::newInstance($attributes, $exists)
            ->setRevisorContext($this->getRevisorContext() ?? Revisor::getContext());
    }

    /**
     * Overrides Model::getTable to return the appropriate
     * table (draft, version, published) based on
     * the current RevisorContext
     */
    public function getTable(): string
    {
        return Revisor::getSuffixedTableNameFor($this->getBaseTable(), $this->getRevisorContext());
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
     * Get the draft record for this model
     *
     * @throws Exception
     */
    public function draftRecord(): HasOne
    {
        if ($this->isDraftTableRecord()) {
            throw new Exception('The draft record HasOne relationship is only available to Published and Version records');
        }

        $instance = (new static)->withDraftContext();
        $localKey = $this->isVersionTableRecord() ? 'record_id' : $this->getKeyName();

        return $this->newHasOne(
            $instance, $this, $instance->getModel()->getTable().'.'.$this->getKeyName(), $localKey
        );
    }

    /**
     * Get a Builder instance for the Draft table
     */
    public function scopeWithDraftContext(Builder $query): Builder
    {
        $query->getModel()->setRevisorContext(RevisorContext::Draft);
        $query->getQuery()->from = $query->getModel()->getTable();

        return $query;
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

    public function setRevisorContext(?RevisorContext $context = null): static
    {
        $this->revisorContext = $context;

        return $this;
    }

    public function getRevisorContext(): ?RevisorContext
    {
        return $this->revisorContext;
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
            $this->versionRecords->each->forceDeleteQuietly() :
            $this->versionRecords->each->deleteQuietly();
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

        $this->currentVersionRecord?->unpublish();
    }

    /**
     * Get the Revisor statuses for the model
     */
    public function getRevisorStatuses(): array
    {
        if (! $this->isPublished()) {
            return ['draft'];
        }

        if (! $this->isRevised()) {
            return ['published'];
        }

        return ['published', 'revised'];
    }
}
