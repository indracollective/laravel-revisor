<?php

declare(strict_types=1);

namespace Indra\Revisor\Concerns;

use Indra\Revisor\Facades\Revisor;

trait HasRevisor
{
    use HasPublishing;
    use HasVersioning;

    public function getBaseTable(): string
    {
        return parent::getTable();
    }

    public function getTable(): string
    {
        if ($this->withPublishedTable) {
            return Revisor::getPublishedTableFor($this->getBaseTable());
        }

        if ($this->withVersionTable) {
            return Revisor::getVersionTableFor($this->getBaseTable());
        }

        return parent::getTable();
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
     */
    protected function fireModelEvent($event, $halt = true): mixed
    {
        if ($this->getTable() !== $this->getBaseTable()) {
            return true;
        }

        if (!isset(static::$dispatcher)) {
            return true;
        }

        return parent::fireModelEvent($event, $halt);
    }
}
