<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Indra\Revisor\Facades\Revisor;

trait HasRevisor
{
    protected string $baseTable;

    protected string $versionTable;

    public function initializeHasRevisor(): void
    {
        $this->setBaseTable($this->getTable());
        $this->setVersionTable(Revisor::getVersionTableFor($this->getTable()));
        $this->setPublishedTable(Revisor::getPublishedTableFor($this->getTable()));

        $this->mergeCasts([
            'published_at' => 'datetime',
            'is_current' => 'boolean',
            'is_published' => 'boolean',
        ]);
    }

    public function publisher(): MorphTo
    {
        return $this->morphTo('publisher');
    }

    public function publish(): static
    {
        // find or make the published record
        $published = static::withPublishedScope()->findOrNew($this->{$this->getKey()});

        // set the published attributes
        $this->published_at = now();
        $this->is_published = true;
        $this->publisher()->associate(auth()->user());

        // copy the attributes from the base record to the published record
        $published->fill($this->attributes);
        $published->title = 'YES!';

        // save the published record quietly as it's effectively
        // a read-only copy of the base record
        $published->saveQuietly();

        // save the base record
        $this->save();

        return $this->fresh();
    }

    public function scopeWithBaseTable(Builder $query): static
    {
        $this->setTable($this->getBaseTable());

        return $this;
    }

    public function scopeWithPublishedTable(Builder $query): static
    {
        $this->setTable($this->getPublishedTable());

        return $this;
    }

    public function scopeWithVersionTable(Builder $query): static
    {
        $this->setTable($this->getVersionTable());

        return $this;
    }

    public function getBaseTable(): string
    {
        return $this->baseTable;
    }

    public function setBaseTable(string $table): static
    {
        $this->baseTable = $table;

        return $this;
    }

    public function getVersionTable(): string
    {
        return $this->versionTable;
    }

    public function setVersionTable(string $table): static
    {
        $this->versionTable = $table;

        return $this;
    }

    public function getPublishedTable(): string
    {
        return $this->publishedTable;
    }

    public function setPublishedTable(string $table): static
    {
        $this->publishedTable = $table;

        return $this;
    }
}
