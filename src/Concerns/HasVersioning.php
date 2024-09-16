<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Indra\Revisor\Contracts\RevisorContract;
use Indra\Revisor\Facades\Revisor;

trait HasVersioning
{
    protected ?bool $recordNewVersionOnCreated = null; // default to config value

    protected ?bool $recordNewVersionOnUpdated = null; // default to config value

    protected bool $withVersionTable = false;

    public static function bootHasVersioning(): void
    {
        static::created(function (RevisorContract $model) {
            if ($model->shouldRecordNewVersionOnCreated()) {
                $model->recordNewVersion();
            }
        });

        static::updated(function (RevisorContract $model) {
            if ($model->shouldRecordNewVersionOnUpdated()) {
                $model->recordNewVersion();
            } else {
                $model->syncCurrentVersion();
            }
        });

        static::saving(function (RevisorContract $model) {
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
     * Create a new record in the version table
     * Ensure it is_current and other versions are not
     */
    public function recordNewVersion(): static|bool
    {
        if ($this->fireModelEvent('savingNewVersion') === false) {
            return false;
        }

        $version = static::withVersionTable();
        $attributes = array_merge(
            $this->attributes,
            [
                'is_current' => 1,
                'record_id' => $this->id,
            ]
        );
        unset($attributes['id']);

        $version->forceFill($attributes)->saveQuietly();

        // update all other versions to not be current
        $this->versions()
            ->where('is_current', 1)
            ->whereNot($version->getKeyName(), $version->getKey())
            ->each(fn ($version) => $version->forceFill(['is_current' => 0])->saveQuietly());

        $this->fireModelEvent('savedNewVersion');

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

    public function syncCurrentVersion(): static|bool
    {
        if (! $this->currentVersion) {
            return $this->recordNewVersion();
        }

        $this->currentVersion->updateQuietly($this->attributes);

        return $this;
    }

    public static function withVersionTable(): static
    {
        $instance = new static;
        $instance->setWithVersionTable();

        return $instance;
    }

    public function setWithPublishedTable(bool $bool = true): static
    {
        $this->withPublishedTable = $bool;

        return $this;
    }

    public function setWithVersionTable(bool $bool = true): static
    {
        $this->withVersionTable = $bool;

        return $this;
    }

    public function recordNewVersionOnCreated(bool $bool = true): static
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

    public function recordNewVersionOnUpdated(bool $bool = true): static
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
