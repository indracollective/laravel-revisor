<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\Contracts\HasVersioning as HasVersioningContract;

trait HasVersioning
{
    protected ?bool $recordNewVersionOnCreated = null; // default to config value

    protected ?bool $recordNewVersionOnUpdated = null; // default to config value

    protected bool $withVersionTable = false;

    public static function bootHasVersioning(): void
    {
        static::created(function (HasVersioningContract $model) {
            if ($model->shouldRecordNewVersionOnCreated()) {
                $model->recordNewVersion();
            }
        });

        static::updated(function (HasVersioningContract $model) {
            if ($model->shouldRecordNewVersionOnUpdated()) {
                $model->recordNewVersion();
            } else {
                $model->syncCurrentVersion();
            }
        });

        static::saving(function (HasVersioningContract $model) {
            $model->is_current = true;
        });

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
        //        if (method_exists(static::class, 'forceDeleted')) {
        //            static::forceDeleted(function (Model $model): void {
        //                $model->revisions()->forceDelete();
        //            });
        //        }
    }

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
     */
    public function recordNewVersion(): HasVersioningContract|bool
    {
        if ($this->fireModelEvent('savingNewVersion') === false) {
            return false;
        }

        $attributes = collect($this->attributes)
            ->except(['id'])
            ->merge([
                'record_id' => $this->id,
                'version_number' => ($this->versions()->max('version_number') ?? 0) + 1
            ])
            ->toArray();

        $this->setVersionAsCurrent(static::withVersionTable(), $attributes);

        $this->fireModelEvent('savedNewVersion');

        return $this;
    }

    public function rollbackToVersion(HasVersioningContract|int $version): HasVersioningContract
    {
        $version = is_int($version) ? $this->versions()->find($version) : $version;

        $this->fireModelEvent('rollingBackToVersion', $version);

        // set the version as current and save it
        $this->setVersionAsCurrent($version);

        // update the current base record to have the data from the version
        $this->forceFill(collect($version->getAttributes())->except(['id', 'record_id'])->toArray())
            ->saveQuietly();

        $this->fireModelEvent('rolledBackToVersion', $version);

        return $this->refresh();
    }

    public function rollbackToVersionNumber(int $versionNumber): HasVersioningContract
    {
        $version = $this->versions()->firstWhere('version_number', $versionNumber);

        return $this->rollbackToVersion($version);
    }

    public function setVersionAsCurrent(HasVersioningContract|int $version, array $attributes = []): HasVersioningContract
    {
        $version = is_int($version) ? $this->versions()->find($version) : $version;

        // update the version record with the given attributes
        $version->forceFill($attributes);

        // update all other versions to not be current
        // and set this version as current and save it
        $this->versions()->where('is_current', 1)->update(['is_current' => 0]);
        $version->forceFill(['is_current' => 1])->saveQuietly();

        // update the current base record to have the new version_number
        if ($this->version_number !== $version->version_number) {
            $this->forceFill(['version_number' => $version->version_number])->saveQuietly();
        }

        $this->refresh();

        return $this;
    }

    public function versions(): HasMany
    {
        $instance = $this->newRelatedInstance(static::class);
        $instance->setWithVersionTable(true);

        return $this->newHasMany(
            $instance->newQuery(), $this, $this->getVersionTable().'.record_id', $this->getKeyName()
        );
    }

    public function currentVersion(): HasOne
    {
        $instance = $this->newRelatedInstance(static::class);
        $instance->setWithVersionTable(true);

        return $this->newHasOne(
            $instance->newQuery(), $this, $instance->getTable().'.record_id', $this->getKeyName()
        )->where('is_current', 1);
    }

    public function syncCurrentVersion(): HasVersioningContract|bool
    {
        if (!$this->currentVersion) {
            return $this->recordNewVersion();
        }

        $this->currentVersion->updateQuietly($this->attributes);

        return $this;
    }

    public static function withVersionTable(): HasVersioningContract
    {
        return app(static::class)->setWithVersionTable();
    }

    public function setWithVersionTable(bool $bool = true): HasVersioningContract
    {
        $this->withVersionTable = $bool;

        return $this;
    }

    public function recordNewVersionOnCreated(bool $bool = true): HasVersioningContract
    {
        $this->recordNewVersionOnCreated = $bool;

        return $this;
    }

    public function shouldRecordNewVersionOnCreated(): bool
    {
        return is_null($this->recordNewVersionOnCreated) ?
            config('revisor.record_new_version_on_created') :
            $this->recordNewVersionOnCreated;
    }

    public function recordNewVersionOnUpdated(bool $bool = true): HasVersioningContract
    {
        $this->recordNewVersionOnUpdated = $bool;

        return $this;
    }

    public function shouldRecordNewVersionOnUpdated(): bool
    {
        return is_null($this->recordNewVersionOnUpdated) ?
            config('revisor.record_new_version_on_updated') :
            $this->recordNewVersionOnUpdated;
    }

    public function getBaseTable(): string
    {
        return parent::getTable();
    }

    public function getVersionTable(): string
    {
        return Revisor::getVersionTableFor($this->getBaseTable());
    }

    public function getTable(): string
    {
        if ($this->withVersionTable) {
            return Revisor::getVersionTableFor($this->getBaseTable());
        }

        return parent::getTable();
    }

    /**
     * Override the fireModelEvent method to prevent events from firing on
     * the version or published tables.
     */
    protected function fireModelEvent($event, $halt = true): mixed
    {
        if ($this->getTable() !== $this->getBaseTable()) {
            return true;
        }

        return parent::fireModelEvent($event, $halt);
    }
}
