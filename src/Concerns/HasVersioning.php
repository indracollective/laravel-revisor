<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Indra\Revisor\Contracts\HasRevisor as HasRevisorContract;
use Indra\Revisor\Enums\RevisorContext;
use Indra\Revisor\Facades\Revisor;

trait HasVersioning
{
    /**
     * Number of versions to keep on this particular model
     * Overrides the global config if not null
     */
    protected null|int|bool $keepVersions = null;

    /**
     * Whether to record a new version when a new instance of the model is created
     * Overrides the global config if true or false
     */
    protected ?bool $saveNewVersionOnCreated = null;

    /**
     * Whether to record a new version when a new instance of the model is updated
     * Overrides the global config if true or false
     */
    protected ?bool $saveNewVersionOnUpdated = null;

    /**
     * Register model event listeners
     */
    public static function bootHasVersioning(): void
    {
        static::created(function (HasRevisorContract $model) {
            if (! $model->isDraftTableRecord()) {
                return;
            }

            if ($model->shouldSaveNewVersionOnCreated()) {
                $model->saveNewVersion();
            }
        });

        static::updated(function (HasRevisorContract $model) {
            if (! $model->isDraftTableRecord()) {
                return;
            }

            if ($model->shouldSaveNewVersionOnUpdated()) {
                $model->saveNewVersion();
            } else {
                $model->syncToCurrentVersionRecord();
            }
        });

        static::saving(function (HasRevisorContract $model) {
            if ($model->isVersionTableRecord()) {
                return;
            }

            $model->is_current = true;
        });

        static::published(function (HasRevisorContract $model) {
            $model->syncToCurrentVersionRecord();
        });

        static::unpublished(function (HasRevisorContract $model) {
            $model->syncToCurrentVersionRecord();
        });
    }

    /**
     * Merge the is_current cast to the model
     */
    public function initializeHasVersioning(): void
    {
        $this->mergeCasts([
            'is_current' => 'boolean',
        ]);
    }

    /**
     * Create a new version of this record in the version table
     * Mark the new version as the current version and not published
     * Update this record to have the version number of the new version
     * Prune old versions if necessary
     */
    public function saveNewVersion(): static|bool
    {
        if ($this->fireModelEvent('savingNewVersion') === false) {
            return false;
        }

        $exceptAttributes = collect(config('revisor.publishing.table_columns'))
            ->values()
            ->add('id')
            ->toArray();

        $attributes = collect($this->attributesToArray())
            ->except($exceptAttributes)
            ->merge([
                'record_id' => $this->id,
                'version_number' => ($this->versionRecords()->max('version_number') ?? 0) + 1,
            ])
            ->toArray();

        $version = static::make()->setRevisorContext(RevisorContext::Version)->forceFill($attributes);
        $this->setVersionAsCurrent($version);

        $this->pruneVersions();

        $this->fireModelEvent('savedNewVersion', $version);

        return $this;
    }

    /**
     * Rollback the Draft table record to the given version
     */
    public function revertToVersion(HasRevisorContract|int|string $version): static
    {
        $version = is_int($version) ? $this->versionRecords()->find($version) : $version;

        $this->fireModelEvent('revertingToVersion', $version);

        // set the version as current and save it
        $this->setVersionAsCurrent($version);

        // update the current draft record to have the data from the version
        // excluding the publishing and versioning columns
        // and updating the updated_at timestamp
        $attributes = collect($version->getAttributes())
            ->except([
                'id',
                config('revisor.versioning.table_columns.record_id'),
                config('revisor.publishing.table_columns.published_at'),
                config('revisor.publishing.table_columns.is_published'),
                config('revisor.publishing.table_columns.publisher').'_id',
                config('revisor.publishing.table_columns.publisher').'_type',
            ])
            ->put('updated_at', now())
            ->toArray();

        $this->forceFill($attributes)->saveQuietly();

        $this->fireModelEvent('revertedToVersion', $version);

        return $this->refresh();
    }

    public function revertToVersionNumber(int $versionNumber): static
    {
        $version = $this->versionRecords()->firstWhere('version_number', $versionNumber);

        return $this->revertToVersion($version);
    }

    /**
     * Revert the Draft record to the state of this Version record
     *
     * @throws Exception if this record is not a Version record
     */
    public function revertDraftToThisVersion(): static
    {
        if (! $this->isVersionTableRecord()) {
            $context = $this->getRevisorContext();
            throw new \Exception("Can not revert this record, it is a $context record. Only Version records can be reverted.");
        }

        $this->draftRecord->revertToVersion($this);

        return $this;
    }

    public function setVersionAsCurrent(HasRevisorContract|int $version): static
    {
        $version = is_int($version) ? $this->versionRecords()->find($version) : $version;

        // update all other versions to not be current
        // and set this version as current and save it
        $this->versionRecords()->where('is_current', 1)->update(['is_current' => 0]);
        $version->forceFill(['is_current' => 1])->saveQuietly();

        // update the draft record to have the new version_number
        if ($this->version_number !== $version->version_number) {
            $this->forceFill([
                config('revisor.versioning.table_columns.version_number') => $version->{config('revisor.versioning.table_columns.version_number')},
            ])->saveQuietly();
        }

        $this->refresh();

        return $this;
    }

    public function versionRecords(): HasMany
    {
        $instance = $this->newRelatedInstance(static::class)->setRevisorContext(RevisorContext::Version);

        return $this->newHasMany(
            $instance->newQuery(), $this, $this->getVersionTable().'.record_id', $this->getKeyName()
        );
    }

    public function currentVersionRecord(): HasOne
    {
        $query = $this->newRelatedInstance(static::class)->withVersionContext();

        return $this->newHasOne(
            $query, $this, $query->getModel()->getTable().'.record_id', $this->getKeyName()
        )->where('is_current', 1);
    }

    public function keepVersions(null|int|bool $keep = true): void
    {
        $this->keepVersions = $keep;
    }

    public function shouldKeepVersions(): int|bool
    {
        if ($this->keepVersions === null) {
            return config('revisor.versioning.keep_versions');
        }

        return $this->keepVersions;
    }

    public function prunableVersions(): HasMany
    {
        $keep = $this->shouldKeepVersions();

        // int = prune the oldest, keeping n versions
        if (is_int($keep)) {
            return $this->versionRecords()->where('is_current', 0)
                ->orderBy('version_number')
                ->skip($keep)
                ->take(PHP_INT_MAX);
        }

        // false = prune all revisions
        if ($keep === false) {
            return $this->versionRecords();
        }

        // true = avoid pruning entirely by returning no prunable versions
        return $this->versionRecords()->whereRaw('1 = 0');
    }

    /**
     * Sync this record's attributes to the current version record
     * Create a new version record if there is no current version
     */
    public function syncToCurrentVersionRecord(): static|bool
    {
        if (! $this->currentVersionRecord) {
            return $this->saveNewVersion();
        }
        $this->fireModelEvent('syncingToCurrentVersion', $this->currentVersionRecord);

        $attributes = collect($this->attributes)
            ->except([$this->getKeyName(), 'version_number'])
            ->toArray();

        $this->currentVersionRecord->forceFill($attributes)->saveQuietly();

        // if this current version is published, ensure no
        // other versions are marked as published
        if ($this->currentVersionRecord->is_published) {
            $this->versionRecords()
                ->whereNot('id', $this->currentVersionRecord->id)
                ->update(['is_published' => 0]);
        }

        $this->fireModelEvent('syncedToCurrentVersion', $this->currentVersionRecord);

        return $this;
    }

    public function pruneVersions(): static
    {
        if (! $this->prunableVersions->count()) {
            return $this;
        }

        if (method_exists($this->prunableVersions->first(), 'softDeleted')) {
            $this->prunableVersions->each->forceDelete();
        } else {
            $this->prunableVersions->each->delete();
        }

        return $this;
    }

    /**
     * Get a Builder instance for the Version table
     */
    public function scopeWithVersionContext(Builder $query): Builder
    {
        $query->getModel()->setRevisorContext(RevisorContext::Version);
        $query->getQuery()->from = $query->getModel()->getTable();

        return $query;
    }

    public function saveNewVersionOnCreated(bool $bool = true): static
    {
        $this->saveNewVersionOnCreated = $bool;

        return $this;
    }

    public function shouldSaveNewVersionOnCreated(): bool
    {
        return is_null($this->saveNewVersionOnCreated) ?
            config('revisor.versioning.save_new_version_on_created') :
            $this->saveNewVersionOnCreated;
    }

    public function saveNewVersionOnUpdated(bool $bool = true): static
    {
        $this->saveNewVersionOnUpdated = $bool;

        return $this;
    }

    public function shouldSaveNewVersionOnUpdated(): bool
    {
        return is_null($this->saveNewVersionOnUpdated) ?
            config('revisor.versioning.save_new_version_on_updated') :
            $this->saveNewVersionOnUpdated;
    }

    public function saveNewVersionOnSaved(bool $bool = true): static
    {
        $this->saveNewVersionOnCreated = $bool;
        $this->saveNewVersionOnUpdated = $bool;

        return $this;
    }

    public function getVersionTable(): string
    {
        return Revisor::getVersionTableFor($this->getBaseTable());
    }

    public function isVersionTableRecord(): bool
    {
        return $this->getTable() === $this->getVersionTable();
    }

    /**
     * Register a "savingNewVersion" model event callback with the dispatcher.
     */
    public static function savingNewVersion(string|Closure $callback): void
    {
        static::registerModelEvent('savingNewVersion', $callback);
    }

    /**
     * Register a "savedNewVersion" model event callback with the dispatcher.
     */
    public static function savedNewVersion(string|Closure $callback): void
    {
        static::registerModelEvent('savedNewVersion', $callback);
    }

    /**
     * Register a "syncingToCurrentVersion" model event callback with the dispatcher.
     */
    public static function syncingToCurrentVersion(string|Closure $callback): void
    {
        static::registerModelEvent('syncingToCurrentVersion', $callback);
    }

    /**
     * Register a "syncedToCurrentVersion" model event callback with the dispatcher.
     */
    public static function syncedToCurrentVersion(string|Closure $callback): void
    {
        static::registerModelEvent('syncedToCurrentVersion', $callback);
    }

    /**
     * Register a "revertingToVersion" model event callback with the dispatcher.
     */
    public static function revertingToVersion(string|Closure $callback): void
    {
        static::registerModelEvent('revertingToVersion', $callback);
    }

    /**
     * Register a "revertedToVersion" model event callback with the dispatcher.
     */
    public static function revertedToVersion(string|Closure $callback): void
    {
        static::registerModelEvent('revertedToVersion', $callback);
    }
}
