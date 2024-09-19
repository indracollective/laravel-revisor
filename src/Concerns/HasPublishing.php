<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Indra\Revisor\Contracts\HasPublishing as HasPublishingContract;
use Indra\Revisor\Facades\Revisor;

trait HasPublishing
{
    /*
     * Whether to publish the record when a new instance of the model is created
     * Overrides the global config if true or false
     **/
    protected ?bool $publishOnCreated = null;

    /*
     * Whether to publish the record when an instance of the model is updated
     * Overrides the global config if true or false
     **/
    protected ?bool $publishOnUpdated = null; // default to config value

    /*
     * Register model event listeners
     **/
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

    /*
     * Merge the published_at and is_published casts to the model
     **/
    public function initializeHasPublishing(): void
    {
        $this->mergeCasts([
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ]);
    }

    /*
     * Get a Builder instance for the Published table
     **/
    public static function withPublishedTable(): Builder
    {
        $instance = new static;

        return $instance->setTable($instance->getPublishedTable())->newQuery();
    }

    /**
     * Publish the model.
     *
     * Sets the draft record to a published state.
     * Copies the draft record to the published table.
     * Saves the updated draft record.
     */
    public function publish(): HasPublishingContract|bool
    {
        if ($this->fireModelEvent('publishing') === false) {
            return false;
        }

        // put the draft record in published state
        $this->setPublishedAttributes();

        // copy the draft record to the published table
        $this->applyStateToPublishedRecord();

        // save the draft record
        $this->saveQuietly();

        // fire the published event
        $this->fireModelEvent('published');

        $this->refresh();

        return $this;
    }

    /**
     * Unpublish the model.
     *
     * Sets the draft record to an unpublished state.
     * Deletes the corresponding record from the published table.
     * Saves the updated draft record.
     * Fires the unpublished event.
     */
    public function unpublish(): HasPublishingContract
    {
        if ($this->fireModelEvent('unpublishing') === false) {
            return $this;
        }

        // put the draft record in unpublished state
        $this->setUnpublishedAttributes();

        // delete the published record
        static::withPublishedTable()
            ->firstWhere($this->getKeyName(), $this->getKey())
            ->deleteQuietly();

        // save the draft record
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

    /*
     * Apply the state of this record to the published record
     **/
    public function applyStateToPublishedRecord(): HasPublishingContract
    {
        // find or make the published record
        $published = $this->publishedRecord ?? static::make()->setTable($this->getPublishedTable());

        // copy the attributes from the draft record to the published record
        $published->forceFill($this->attributes);

        // save the published record
        $published->save();

        return $this;
    }

    /*
     * Set the publishing related attributes on
     * the model to their unpublished state
     **/
    public function setUnpublishedAttributes(): HasPublishingContract
    {
        $this->published_at = null;
        $this->is_published = false;
        $this->publisher()->dissociate();

        return $this;
    }

    /*
     * Get the published record for this model
     **/
    public function publishedRecord(): HasOne
    {
        $instance = static::withPublishedTable();

        return $this->newHasOne(
            $instance, $this, $instance->getModel()->getTable().'.'.$this->getKeyName(), $this->getKeyName()
        );
    }

    /*
     * Get the publisher relationship for this model
     **/
    public function publisher(): MorphTo
    {
        return $this->morphTo('publisher');
    }

    /*
     * Set whether to publish the record when a new instance of the model is created
     **/
    public function publishOnCreated(bool $bool = true): HasPublishingContract
    {
        $this->publishOnCreated = $bool;

        return $this;
    }

    /*
     * Set whether to publish the record when an instance of the model is updated
     **/
    public function publishOnUpdated(bool $bool = true): HasPublishingContract
    {
        $this->publishOnUpdated = $bool;

        return $this;
    }

    /*
     * Get whether to publish the record when a new instance of the model is created
     **/
    public function shouldPublishOnCreated(): bool
    {
        return is_null($this->publishOnCreated) ? config('revisor.publishing.publish_on_created') : $this->publishOnCreated;
    }

    /*
     * Get whether to publish the record when an instance of the model is updated
     **/
    public function shouldPublishOnUpdated(): bool
    {
        return is_null($this->publishOnUpdated) ? config('revisor.publishing.publish_on_updated') : $this->publishOnUpdated;
    }

    /*
     * Get the Published table name for the model
     **/
    public function getPublishedTable(): string
    {
        return Revisor::getPublishedTableFor($this->getBaseTable());
    }

    /*
     * Check if the model is a Published table record
     **/
    public function isPublishedTableRecord(): bool
    {
        return $this->getTable() === $this->getPublishedTable();
    }

    /**
     * Register a "publishing" model event callback with the dispatcher.
     */
    public static function publishing(string|Closure $callback): void
    {
        static::registerModelEvent('publishing', $callback);
    }

    /**
     * Register a "published" model event callback with the dispatcher.
     */
    public static function published(string|Closure $callback): void
    {
        static::registerModelEvent('published', $callback);
    }

    /**
     * Register a "unpublishing" model event callback with the dispatcher.
     */
    public static function unpublishing(string|Closure $callback): void
    {
        static::registerModelEvent('unpublishing', $callback);
    }

    /**
     * Register a "unpublished" model event callback with the dispatcher.
     */
    public static function unpublished(string|Closure $callback): void
    {
        static::registerModelEvent('unpublished', $callback);
    }
}
