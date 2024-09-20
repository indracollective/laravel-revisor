<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Indra\Revisor\Contracts\HasRevisor as HasRevisorContract;
use Indra\Revisor\Facades\Revisor;

trait HasVersioning
{
    /*
     * Number of versions to keep on this particular model
     * Overrides the global config if not null
     **/
    protected null|int|bool $keepVersions = null;

    /*
     * Whether to record a new version when a new instance of the model is created
     * Overrides the global config if true or false
     **/
    protected ?bool $recordNewVersionOnCreated = null;

    /*
     * Whether to record a new version when a new instance of the model is updated
     * Overrides the global config if true or false
     **/
    protected ?bool $recordNewVersionOnUpdated = null;

    /*
     * Register model event listeners
     **/
    public static function bootHasVersioning(): void
    {
        static::created(function (HasRevisorContract $model) {
            if (! $model->isDraftTableRecord()) {
                return;
            }

            if ($model->shouldRecordNewVersionOnCreated()) {
                $model->recordNewVersion();
            }
        });

        static::updated(function (HasRevisorContract $model) {
            if (! $model->isDraftTableRecord()) {
                return;
            }

            if ($model->shouldRecordNewVersionOnUpdated()) {
                $model->recordNewVersion();
            } else {
                $model->syncCurrentVersion();
            }
        });

        static::saving(function (HasRevisorContract $model) {
            if ($model->isVersionTableRecord()) {
                return;
            }

            $model->is_current = true;
        });

        static::deleted(function (HasRevisorContract $model) {
            // Remove version number from base record if it has the
            // version_number of the version being deleted
            if ($model->isVersionTableRecord()) {
                $draftRecord = static::withDraftTable()->find($model->record_id);

                if ($draftRecord && $draftRecord->version_number === $model->version_number) {
                    $draftRecord->version_number = null;
                    $draftRecord->save();
                }
            }
        });

        //        static::softDeleted(function (HasRevisor $model) {
        //            // Remove version number from base record if it has the
        //            // version_number of the version being deleted
        //            if ($model->isVersionTableRecord()) {
        //                $baseRecord = app(static::class)->find($model->record_id);
        //                if ($baseRecord && $baseRecord->version_number === $model->version_number) {
        //                    $baseRecord->version_number = null;
        //                    $baseRecord->save();
        //                }
        //            }
        //        });

        //        static::deleted(function (Model $model): void {
        //            $model->revisions()->delete();
        //        });
        //
        //        if (method_exists(static::class, 'restored')) {
        //            static::restored(function (Model $model): void {
        //                $model->revisions()->restore();
        //            });
        //        }
        //
        //        if (method_exists(static::class, 'forceDelete')) {
        //            static::forceDeleted(function (Model $model): void {
        //                $model->revisions()->forceDelete();
        //            });
        //        }
    }

    /*
     * Merge the is_current cast to the model
     **/
    public function initializeHasVersioning(): void
    {
        $this->mergeCasts([
            'is_current' => 'boolean',
        ]);
    }

    /**
     * Creates a new record in the version table
     * Ensures it is_current and other versions are not
     * Updates the current base record to have the new version_number
     * Prunes old versions
     */
    public function recordNewVersion(): HasRevisorContract|bool
    {
        if ($this->fireModelEvent('savingNewVersion') === false) {
            return false;
        }

        $attributes = collect($this->attributes)
            ->except(['id'])
            ->merge([
                'record_id' => $this->id,
                'version_number' => ($this->versions()->max('version_number') ?? 0) + 1,
            ])
            ->toArray();

        $version = static::make()->setTable($this->getVersionTable())->forceFill($attributes);
        $this->setVersionAsCurrent($version);

        $this->pruneVersions();

        $this->fireModelEvent('savedNewVersion');

        return $this;
    }

    /*
     * Rollback the Draft table record to the given version
     **/
    public function revertToVersion(HasRevisorContract|int $version): HasRevisorContract
    {
        $version = is_int($version) ? $this->versions()->find($version) : $version;

        $this->fireModelEvent('revertingToVersion', $version);

        // set the version as current and save it
        $this->setVersionAsCurrent($version);

        // update the current draft record to have the data from the version
        $attributes = collect($version->getAttributes())->except(['id', 'record_id'])->toArray();
        $this->forceFill($attributes)->saveQuietly();

        $this->fireModelEvent('revertedToVersion', $version);

        return $this->refresh();
    }

    public function revertToVersionNumber(int $versionNumber): HasRevisorContract
    {
        $version = $this->versions()->firstWhere('version_number', $versionNumber);

        return $this->revertToVersion($version);
    }

    public function setVersionAsCurrent(HasRevisorContract|int $version): HasRevisorContract
    {
        $version = is_int($version) ? $this->versions()->find($version) : $version;

        // update all other versions to not be current
        // and set this version as current and save it
        $this->versions()->where('is_current', 1)->update(['is_current' => 0]);
        $version->forceFill(['is_current' => 1])->saveQuietly();

        // update the current draft record to have the new version_number
        if ($this->version_number !== $version->version_number) {
            $this->forceFill(['version_number' => $version->version_number])->saveQuietly();
        }

        $this->refresh();

        return $this;
    }

    public function versions(): HasMany
    {
        $instance = $this->newRelatedInstance(static::class)->setTable($this->getVersionTable());

        return $this->newHasMany(
            $instance->newQuery(), $this, $this->getVersionTable().'.record_id', $this->getKeyName()
        );
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

        // int = prune the oldest, keeping n revisions
        if (is_int($keep)) {
            return $this->versions()->where('is_current', 0)
                ->orderBy('version_number')
                ->skip($keep)
                ->take(PHP_INT_MAX);
        }

        // false = prune all revisions
        if ($keep === false) {
            return $this->versions();
        }

        // true = avoid pruning entirely by returning no prunable versions
        return $this->versions()->whereRaw('1 = 0');
    }

    public function currentVersion(): HasOne
    {
        $instance = $this->newRelatedInstance(static::class)
            ->setTable(Revisor::getVersionTableFor($this->getBaseTable()));

        return $this->newHasOne(
            $instance->newQuery(), $this, $instance->getTable().'.record_id', $this->getKeyName()
        )->where('is_current', 1);
    }

    public function syncCurrentVersion(): HasRevisorContract|bool
    {
        if (! $this->currentVersion) {
            return $this->recordNewVersion();
        }

        $this->currentVersion->updateQuietly($this->attributes);

        return $this;
    }

    public function pruneVersions(): HasRevisorContract
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

    /*
     * Get a Builder instance for the Version table
     **/
    public static function withVersionTable(): Builder
    {
        $instance = new static;

        return $instance->setTable($instance->getVersionTable())->newQuery();
    }

    public function recordNewVersionOnCreated(bool $bool = true): HasRevisorContract
    {
        $this->recordNewVersionOnCreated = $bool;

        return $this;
    }

    public function shouldRecordNewVersionOnCreated(): bool
    {
        return is_null($this->recordNewVersionOnCreated) ?
            config('revisor.versioning.record_new_version_on_created') :
            $this->recordNewVersionOnCreated;
    }

    public function recordNewVersionOnUpdated(bool $bool = true): HasRevisorContract
    {
        $this->recordNewVersionOnUpdated = $bool;

        return $this;
    }

    public function shouldRecordNewVersionOnUpdated(): bool
    {
        return is_null($this->recordNewVersionOnUpdated) ?
            config('revisor.versioning.record_new_version_on_updated') :
            $this->recordNewVersionOnUpdated;
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
