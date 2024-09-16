<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Indra\Revisor\Contracts\RevisorContract;
use Indra\Revisor\Facades\Revisor;

trait HasPublishing
{
    protected bool | null $publishOnCreated = null; // default to config value

    protected bool | null $publishOnUpdated = null; // default to config value

    protected bool $withPublishedTable = false;

    public static function bootHasPublishing(): void
    {
        static::created(function (RevisorContract $model) {
            if ($model->shouldPublishOnCreated()) {
                $model->publish();
            }
        });

        static::updated(function (RevisorContract $model) {
            if ($model->shouldPublishOnUpdated()) {
                $model->publish();
            }
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

    public function initializeHasPublishing(): void
    {
        $this->mergeCasts([
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ]);
    }

    /**
     * Publish the model.
     *
     * Sets the base record to a published state.
     * Copies the base record to the published table.
     * Saves the updated base record.
     */
    public function publish(): static|bool
    {
        if ($this->fireModelEvent('publishing') === false) {
            return false;
        }

        // put the base record in published state
        $this->setPublishedAttributes();

        // copy the base record to the published table
        $this->applyStateToPublishedRecord();

        // save the base record
        $this->saveQuietly();

        // fire the published event
        $this->fireModelEvent('published');

        return $this->fresh();
    }

    /**
     * Unpublish the model.
     *
     * Sets the base record to an unpublished state.
     * Deletes the corresponding record from the published table.
     * Saves the updated base record.
     * Fires the unpublished event.
     */
    public function unpublish(): static
    {
        if ($this->fireModelEvent('unpublishing') === false) {
            return $this;
        }

        // put the base record in unpublished state
        $this->setUnpublishedAttributes();

        // delete the published record
        static::withPublishedTable()
            ->firstWhere($this->getKeyName(), $this->getKey())
            ->deleteQuietly();

        // save the base record
        $this->save();

        // fire the unpublished event
        $this->fireModelEvent('unpublished');

        return $this->fresh();
    }

    /**
     * Set the published attributes on the model.
     *
     * Updates the published_at timestamp, sets is_published to true,
     * and associates the current authenticated user as the publisher.
     */
    public function setPublishedAttributes(): static
    {
        $this->published_at = now();
        $this->is_published = true;
        $this->publisher()->associate(auth()->user());

        return $this;
    }

    public function applyStateToPublishedRecord(): static
    {
        // find or make the published record
        $published = static::withPublishedTable()->findOrNew($this->{$this->getKey()});

        // copy the attributes from the base record to the published record
        $published->forceFill($this->attributes);

        // save the published record quietly as it's effectively
        // a read-only copy of the base record
        $published->saveQuietly();

        return $this;
    }

    public function setUnpublishedAttributes(): static
    {
        $this->published_at = null;
        $this->is_published = false;
        $this->publisher()->dissociate();

        return $this;
    }

    public function publishedRecord(): HasOne
    {
        $instance = $this->newRelatedInstance(static::class);
        $instance->setWithPublishedTable(true);
        return $this->newHasOne(
            $instance->newQuery(), $this, $instance->getTable().'.'.$this->getKeyName(), $this->getKeyName()
        );
    }

    public function publisher(): MorphTo
    {
        return $this->morphTo('publisher');
    }

    public static function withPublishedTable(): static
    {
        $instance = new static;
        $instance->setWithPublishedTable();

        return $instance;
    }

    public function setWithPublishedTable(bool $bool = true): static
    {
        $this->withPublishedTable = $bool;

        return $this;
    }

    public function publishOnCreated(bool $bool = true): static
    {
        $this->publishOnCreated = $bool;

        return $this;
    }

    public function publishOnUpdated(bool $bool = true): static
    {
        $this->publishOnUpdated = $bool;

        return $this;
    }

    public function shouldPublishOnCreated(): bool
    {
        return is_null($this->publishOnCreated) ? config('revisor.publish_on_created') : $this->publishOnCreated;
    }

    public function shouldPublishOnUpdated(): bool
    {
        return is_null($this->publishOnUpdated) ? config('revisor.publish_on_updated') : $this->publishOnUpdated;
    }

    public function getPublishedTable(): string
    {
        return Revisor::getPublishedTableFor($this->getBaseTable());
    }
}
