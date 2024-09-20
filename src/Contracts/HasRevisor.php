<?php

declare(strict_types=1);

namespace Indra\Revisor\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

interface HasRevisor
{
    public function getTable(): string;

    public function getBaseTable(): string;

    public function getDraftTable(): string;

    public static function withDraftTable(): Builder;

    public function isDraftTableRecord(): bool;

    public static function bootHasPublishing(): void;

    public static function withPublishedTable(): Builder;

    public function publish(): HasRevisor|bool;

    public function unpublish(): HasRevisor;

    public function setPublishedAttributes(): HasRevisor;

    public function applyStateToPublishedRecord(): HasRevisor;

    public function setUnpublishedAttributes(): HasRevisor;

    public function publishedRecord(): HasOne;

    public function publisher(): MorphTo;

    public function publishOnCreated(bool $bool = true): HasRevisor;

    public function publishOnUpdated(bool $bool = true): HasRevisor;

    public function shouldPublishOnCreated(): bool;

    public function shouldPublishOnUpdated(): bool;

    public function getPublishedTable(): string;

    public function isPublishedTableRecord(): bool;

    public static function publishing(string|Closure $callback): void;

    public static function published(string|Closure $callback): void;

    public static function unpublishing(string|Closure $callback): void;

    public static function unpublished(string|Closure $callback): void;

    public static function bootHasVersioning(): void;

    public function initializeHasVersioning(): void;

    public function recordNewVersion(): HasRevisor|bool;

    public function revertToVersion(HasRevisor|int $version): HasRevisor;

    public function revertToVersionNumber(int $versionNumber): HasRevisor;

    public function setVersionAsCurrent(HasRevisor|int $version): HasRevisor;

    public function versions(): HasMany;

    public function keepVersions(null|int|bool $keep = true): void;

    public function shouldKeepVersions(): int|bool;

    public function prunableVersions(): HasMany;

    public function currentVersion(): HasOne;

    public function syncCurrentVersion(): HasRevisor|bool;

    public function pruneVersions(): HasRevisor;

    public static function withVersionTable(): Builder;

    public function recordNewVersionOnCreated(bool $bool = true): HasRevisor;

    public function shouldRecordNewVersionOnCreated(): bool;

    public function recordNewVersionOnUpdated(bool $bool = true): HasRevisor;

    public function shouldRecordNewVersionOnUpdated(): bool;

    public function getVersionTable(): string;

    public function isVersionTableRecord(): bool;

    public static function savingNewVersion(string|Closure $callback): void;

    public static function savedNewVersion(string|Closure $callback): void;

    public static function revertingToVersion(string|Closure $callback): void;

    public static function revertedToVersion(string|Closure $callback): void;
}