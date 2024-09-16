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
        $this->withVersionTable = !$bool;

        return $this;
    }

    public function setWithVersionTable(bool $bool = true): static
    {
        $this->withVersionTable = $bool;
        $this->withPublishedTable = !$bool;

        return $this;
    }

    /**
     * TODO: This needs a rethink...
     * Override the fireModelEvent method to prevent events from firing on
     * the version or published tables.
     */
    protected function fireModelEvent($event, $halt = true): mixed
    {
        if ($this->getTable() !== $this->getBaseTable()) {
            return true;
        }

        if (! isset(static::$dispatcher)) {
            return true;
        }

        // First, we will get the proper method to call on the event dispatcher, and then we
        // will attempt to fire a custom, object based event for the given event. If that
        // returns a result we can return that result, or we'll call the string events.
        $method = $halt ? 'until' : 'dispatch';

        $result = $this->filterModelEventResults(
            $this->fireCustomModelEvent($event, $method)
        );

        if ($result === false) {
            return false;
        }

        return ! empty($result) ? $result : static::$dispatcher->{$method}(
            "eloquent.{$event}: ".static::class, $this
        );
    }

}
