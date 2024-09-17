<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Illuminate\Support\Str;
use Indra\Revisor\Facades\Revisor;

trait HasRevisor
{
    use HasPublishing;
    use HasVersioning;

    /*
     * Reimplementation of the getTable method to allow for a custom / dynamic
     * getTable method on this trait, that returns the contextually
     * appropriate table (base, version, published)
     * */
    public function getBaseTable(): string
    {
        return $this->table ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    public function getTable(): string
    {
        if ($this->withPublishedTable) {
            return Revisor::getPublishedTableFor($this->getBaseTable());
        }

        if ($this->withVersionTable) {
            return Revisor::getVersionTableFor($this->getBaseTable());
        }

        return $this->getBaseTable();
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
