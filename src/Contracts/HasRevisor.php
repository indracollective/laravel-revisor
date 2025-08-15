<?php

declare(strict_types=1);

namespace Indra\Revisor\Contracts;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property bool $is_published
 * @property Carbon $published_at
 * @property Model $publisher
 * @property string $publisher_name
 * @property bool $is_current
 * @property int $version_number
 * @property int $record_id
 *
 * @phpstan-require-extends Model
 */
interface HasRevisor
{
    public function getTable(): string;

    public function getBaseTable(): string;

    public function getDraftTable(): string;

    public function isDraftTableRecord(): bool;

    public static function bootHasPublishing(): void;

    public function publish(): static|bool;

    public function unpublish(): static;

    public function setPublishedAttributes(): static;

    public function applyStateToPublishedRecord(): static;

    public function setUnpublishedAttributes(): static;

    public function publishedRecord(): HasOne;

    public function publisher(): MorphTo;

    public function getPublisherNameAttribute(): ?string;

    public function publishOnCreated(bool $bool = true): static;

    public function publishOnUpdated(bool $bool = true): static;

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

    public function saveNewVersion(): static|bool;

    public function revertToVersion(self|int|string $version): static;

    public function revertToVersionNumber(int $versionNumber): static;

    public function revertDraftToThisVersion(): static;

    public function setVersionAsCurrent(self|int|string $version): static;

    public function versionRecords(): HasMany;

    public function keepVersions(null|int|bool $keep = true): void;

    public function shouldKeepVersions(): int|bool;

    public function prunableVersions(): HasMany;

    public function currentVersionRecord(): HasOne;

    public function syncToCurrentVersionRecord(): static|bool;

    public function pruneVersions(): static;

    public function saveNewVersionOnCreated(bool $bool = true): static;

    public function shouldSaveNewVersionOnCreated(): bool;

    public function saveNewVersionOnUpdated(bool $bool = true): static;

    public function saveNewVersionOnSaved(bool $bool = true): static;

    public function shouldSaveNewVersionOnUpdated(): bool;

    public function getVersionTable(): string;

    public function isVersionTableRecord(): bool;

    public static function savingNewVersion(string|Closure $callback): void;

    public static function savedNewVersion(string|Closure $callback): void;

    public static function revertingToVersion(string|Closure $callback): void;

    public static function revertedToVersion(string|Closure $callback): void;
}
