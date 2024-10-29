<?php

declare(strict_types=1);

use Indra\Revisor\Tests\Models\Page;

it('sets is_current to true on save', function () {
    $page = Page::create(['title' => 'Homes']);
    $page->refresh();
    expect($page->is_current)->toBeTrue();
});

it('creates a new version on created only when configured to do so', function () {
    // global on
    $page = Page::create(['title' => 'Home']);

    expect($page->versionRecords()->count())->toBe(1)
        ->and($page->currentVersionRecord)->toBeInstanceOf(Page::class)
        ->and($page->currentVersionRecord->title)->toBe('Home');

    // global off
    config()->set('revisor.versioning.save_new_version_on_created', false);
    $page = Page::create(['title' => 'About']);
    expect($page->versionRecords()->count())->toBe(0);

    // global off + instance on
    $page = Page::make(['title' => 'Services']);
    $page->saveNewVersionOnCreated();
    $page->save();
    expect($page->versionRecords()->count())->toBe(1);
});

it('creates a new version on updated only when configured to do so', function () {
    // global on
    $page = Page::create(['title' => 'Home']);
    expect($page->versionRecords()->count())->toBe(1);

    $page->update(['title' => 'Home 2']);
    expect($page->versionRecords()->count())->toBe(2);

    // global off
    config()->set('revisor.versioning.save_new_version_on_updated', false);
    $page->update(['title' => 'Home 3']);
    expect($page->versionRecords()->count())->toBe(2);

    // global off + instance on
    $page->saveNewVersionOnUpdated()->update(['title' => 'Home 4']);
    expect($page->versionRecords()->count())->toBe(3);
});

it('numbers versions sequentially', function () {
    $page = Page::create(['title' => 'Home']);
    $page->publish();

    expect($page->currentVersionRecord->version_number)->toBe(1)
        ->and($page->version_number)->toBe(1)
        ->and($page->publishedRecord->version_number)->toBe(1);

    $page->update(['title' => 'Home 2']);
    $page->publish();

    expect($page->currentVersionRecord->version_number)->toBe(2)
        ->and($page->version_number)->toBe(2)
        ->and($page->publishedRecord->version_number)->toBe(2)
        ->and($page->versionRecords()->count())->toBe(2);
});

it('can rollback versions', function () {
    $page = Page::create(['title' => 'Home']);
    $page->update(['title' => 'Home 2']);

    expect($page->versionRecords()->count())->toBe(2)
        ->and($page->currentVersionRecord->version_number)->toBe(2);

    // rollback to version object
    $page->revertToVersion($page->versionRecords->first());
    expect($page->currentVersionRecord->version_number)->toBe(1)
        ->and($page->currentVersionRecord->title)->toBe('Home');

    // rollback to version id
    $page->revertToVersion($page->versionRecords->last()->id);
    expect($page->currentVersionRecord->version_number)->toBe(2)
        ->and($page->currentVersionRecord->title)->toBe('Home 2');

    // rollback to version number
    $page->revertToVersionNumber(1);
    expect($page->currentVersionRecord->version_number)->toBe(1)
        ->and($page->currentVersionRecord->title)->toBe('Home');
});

it('prunes old versions correctly with global config', function () {
    // no pruning
    config()->set('revisor.versioning.keep_versions', true);

    $page = Page::create(['title' => 'Home']);
    $page->update(['title' => 'Home 2']);
    $page->update(['title' => 'Home 3']);
    $page->update(['title' => 'Home 4']);

    expect($page->versionRecords()->count())->toBe(4)
        ->and($page->currentVersionRecord->version_number)->toBe(4);

    // prune n
    config()->set('revisor.versioning.keep_versions', 2);
    $page->update(['title' => 'Home 5']);
    expect($page->versionRecords()->where('is_current', 0)->count())->toBe(2)
        ->and($page->currentVersionRecord->version_number)->toBe(5);

    // prune all
    config()->set('revisor.versioning.keep_versions', false);
    $page->update(['title' => 'Home 6']);
    $page->refresh();

    expect($page->versionRecords()->count())->toBe(0)
        ->and($page->version_number)->toBeNull()
        ->and($page->currentVersionRecord)->toBeNull();
});

it('removes version number from draft and published records when version deleted', function () {
    $page = Page::create(['title' => 'Home']);
    $page->update(['title' => 'Home 2']);
    $page->publish();

    $page->versionRecords()->latest('id')->first()->delete();
    $page->refresh();

    expect($page->versionRecords()->count())->toBe(1)
        ->and($page->versionRecords->first()->version_number)->toBe(1)
        ->and($page->version_number)->toBeNull()
        ->and($page->publishedRecord->version_number)->toBeNull();
});
