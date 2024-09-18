<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Indra\Revisor\Contracts\HasPublishing as HasPublishingContract;
use Indra\Revisor\Facades\Revisor;

trait HasPublishing
{
    protected ?bool $publishOnCreated = null; // default to config value

    protected ?bool $publishOnUpdated = null; // default to config value

    public static function bootHasPublishing(): void
    {
        static::created(function (HasPublishingContract $model) {
            if ($model->shouldPublishOnCreated()) {
                $model->publish();
            }
        });

        static::updated(function (HasPublishingContract $model) {
            if ($model->shouldPublishOnUpdated()) {
                $model->publish();
            }
        });
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
    public function publish(): HasPublishingContract|bool
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

        $this->refresh();

        return $this;
    }

    /**
     * Unpublish the model.
     *
     * Sets the base record to an unpublished state.
     * Deletes the corresponding record from the published table.
     * Saves the updated base record.
     * Fires the unpublished event.
     */
    public function unpublish(): HasPublishingContract
    {
        if ($this->fireModelEvent('unpublishing') === false) {
            return $this;
        }

        // put the base record in unpublished state
        $this->setUnpublishedAttributes();

        // delete the published record
        app(static::class)->withPublishedTable()
            ->firstWhere($this->getKeyName(), $this->getKey())
            ->deleteQuietly();

        // save the base record
        $this->save();

        // fire the unpublished event
        $this->fireModelEvent('unpublished');

        $this->refresh();

        return $this;
    }

    /**
     * Set the published attributes on the model.
     *
     * Updates the published_at timestamp, sets is_published to true,
     * and associates the current authenticated user as the publisher.
     */
    public function setPublishedAttributes(): HasPublishingContract
    {
        $this->published_at = now();
        $this->is_published = true;
        $this->publisher()->associate(auth()->user());

        return $this;
    }

    public function applyStateToPublishedRecord(): HasPublishingContract
    {
        // find or make the published record
        $published = $this->publishedRecord ?? static::withPublishedTable();

        // copy the attributes from the base record to the published record
        $published->forceFill($this->attributes);

        // save the published record quietly as it's effectively
        // a read-only copy of the base record
        $published->saveQuietly();

        return $this;
    }

    public function setUnpublishedAttributes(): HasPublishingContract
    {
        $this->published_at = null;
        $this->is_published = false;
        $this->publisher()->dissociate();

        return $this;
    }

    public function publishedRecord(): HasOne
    {
        $instance = $this->newRelatedInstance(static::class)
            ->setTable($this->getPublishedTable());

        return $this->newHasOne(
            $instance->newQuery(), $this, $instance->getTable().'.'.$this->getKeyName(), $this->getKeyName()
        );
    }

    public function publisher(): MorphTo
    {
        return $this->morphTo('publisher');
    }

    public static function withPublishedTable(): HasPublishingContract
    {
        return app(static::class)->newPublishedInstance();
    }

    public function publishOnCreated(bool $bool = true): HasPublishingContract
    {
        $this->publishOnCreated = $bool;

        return $this;
    }

    public function publishOnUpdated(bool $bool = true): HasPublishingContract
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

    public function isPublishedTableRecord(): bool
    {
        return $this->getTable() === $this->getPublishedTable();
    }
}
