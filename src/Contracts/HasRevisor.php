<?php

declare(strict_types=1);

namespace Indra\Revisor\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

interface HasRevisor
{
    public function getTable(): string;

    public function getBaseTable(): string;

    public function getDraftTable(): string;

    public function isDraftTableRecord(): bool;

    public static function bootHasPublishing(): void;

    public function publish(): HasRevisor|bool;

    public function unpublish(): HasRevisor;

    public function setPublishedAttributes(): HasRevisor;

    public function applyStateToPublishedRecord(): HasRevisor;

    public function setUnpublishedAttributes(): HasRevisor;

    public function publishedRecord(): HasOne;

    public function publisher(): MorphTo;

    public function getPublisherNameAttribute(): ?string;

    public function publishOnCreated(bool $bool = true): HasRevisor;

    public function publishOnUpdated(bool $bool = true): HasRevisor;

    public function shouldPublishOnCreated(): bool;

    public function shouldPublishOnUpdated(): bool;

    public function getPublishedTable(): string;

    public function isPublishedTableRecord(): bool;

    public function isPublished(): bool;

    public function isRevised(): bool;

    public function isUnpublishedOrRevised(): bool;

    public static function publishing(string|Closure $callback): void;

    public static function published(string|Closure $callback): void;

    public static function unpublishing(string|Closure $callback): void;

    public static function unpublished(string|Closure $callback): void;

    public static function bootHasVersioning(): void;

    public function initializeHasVersioning(): void;

    public function saveNewVersion(): HasRevisor|bool;

    public function revertToVersion(HasRevisor|int $version): HasRevisor;

    public function revertToVersionNumber(int $versionNumber): HasRevisor;

    public function revertDraftToThisVersion(): HasRevisor;

    public function setVersionAsCurrent(HasRevisor|int $version): HasRevisor;

    public function versionRecords(): HasMany;

    public function keepVersions(null|int|bool $keep = true): void;

    public function shouldKeepVersions(): int|bool;

    public function prunableVersions(): HasMany;

    public function currentVersionRecord(): HasOne;

    public function syncToCurrentVersionRecord(): HasRevisor|bool;

    public function pruneVersions(): HasRevisor;

    public function saveNewVersionOnCreated(bool $bool = true): HasRevisor;

    public function shouldSaveNewVersionOnCreated(): bool;

    public function saveNewVersionOnUpdated(bool $bool = true): HasRevisor;

    public function saveNewVersionOnSaved(bool $bool = true): HasRevisor;

    public function shouldSaveNewVersionOnUpdated(): bool;

    public function getVersionTable(): string;

    public function isVersionTableRecord(): bool;

    public static function savingNewVersion(string|Closure $callback): void;

    public static function savedNewVersion(string|Closure $callback): void;

    public static function revertingToVersion(string|Closure $callback): void;

    public static function revertedToVersion(string|Closure $callback): void;
}
