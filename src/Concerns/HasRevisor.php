<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Illuminate\Support\Str;
use Indra\Revisor\Contracts\HasPublishing as HasPublishingContract;

trait HasRevisor
{
    use HasPublishing;
    use HasVersioning;

    protected string $baseTable;

    //    public function newInstance($attributes = [], $exists = false)
    //    {
    //        $model = parent::newInstance($attributes, $exists);
    //        $model->setTable($this->getBaseTable());
    //
    //        return $model;
    //    }

    public function newPublishedInstance($attributes = [], $exists = false): HasPublishingContract
    {
        $model = $this->newInstance($attributes, $exists);
        $model->setTable($this->getPublishedTable());

        return $model;
    }

    public function newVersionInstance($attributes = [], $exists = false): HasPublishingContract
    {
        $model = $this->newInstance($attributes, $exists);
        $model->setTable($this->getVersionTable());

        return $model;
    }

    public function newBaseInstance($attributes = [], $exists = false): HasPublishingContract
    {
        $model = $this->newInstance($attributes, $exists);
        $model->setTable($this->getBaseTable());

        return $model;
    }

    /*
     * Reimplementation of the getTable method to allow for a custom / dynamic
     * getTable method on this trait, that returns the contextually
     * appropriate table (base, version, published)
     * */
    public function getBaseTable(): string
    {
        return $this->baseTable ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    public function getTable(): string
    {
        return $this->table ?? $this->getBaseTable();
    }

    public function isBaseTableRecord(): bool
    {
        return $this->getTable() === $this->getBaseTable();
    }

    public function setWithPublishedTable(bool $bool = true): static
    {
        $this->withPublishedTable = $bool;
        $this->withVersionTable = ! $bool;

        return $this;
    }

    public function setWithVersionTable(bool $bool = true): static
    {
        $this->withVersionTable = $bool;
        $this->withPublishedTable = ! $bool;

        return $this;
    }

    /**
     * Override the fireModelEvent method to prevent events from firing on
     * the version or published tables.
     * todo: remove this when the event system is refactored
     */
    protected function fireModelEvent($event, $halt = true): mixed
    {
        if ($this->getTable() !== $this->getBaseTable()) {
            return true;
        }

        if (! isset(static::$dispatcher)) {
            return true;
        }

        return parent::fireModelEvent($event, $halt);
    }
}
