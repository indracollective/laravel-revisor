<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\Tests\Models\Page;

beforeEach(function () {
    Revisor::getAllTablesFor('pages')->each(fn ($table) => DB::table($table)->truncate());
});

it('sets is_current to true on save', function () {
    $page = Page::create(['title' => 'Homes']);
    $page->refresh();
    expect($page->is_current)->toBeTrue();
});

it('creates a new version on created only when configured to do so', function () {
    // global on
    $page = Page::create(['title' => 'Home']);
    expect($page->versions()->count())->toBe(1)
        ->and($page->currentVersion)->toBeInstanceOf(Page::class)
        ->and($page->currentVersion->title)->toBe('Home');

    // global off
    config()->set('revisor.record_new_version_on_created', false);
    $page = Page::create(['title' => 'About']);
    expect($page->versions()->count())->toBe(0);

    // global off + instance on
    $page = Page::make(['title' => 'Services']);
    $page->recordNewVersionOnCreated();
    $page->save();
    expect($page->versions()->count())->toBe(1);
});

it('creates a new version on updated only when configured to do so', function () {
    // global on
    $page = Page::create(['title' => 'Home']);
    expect($page->versions()->count())->toBe(1);

    $page->update(['title' => 'Home 2']);
    expect($page->versions()->count())->toBe(2);

    // global off
    config()->set('revisor.record_new_version_on_updated', false);
    $page->update(['title' => 'Home 3']);
    expect($page->versions()->count())->toBe(2);

    // global off + instance on
    $page->recordNewVersionOnUpdated()->update(['title' => 'Home 4']);
    expect($page->versions()->count())->toBe(3);
});

it('numbers versions sequentially', function () {
    $page = Page::create(['title' => 'Home']);
    $page->publish();

    expect($page->currentVersion->version_number)->toBe(1)
        ->and($page->version_number)->toBe(1)
        ->and($page->publishedRecord->version_number)->toBe(1);

    $page->update(['title' => 'Home 2']);
    $page->publish();

    expect($page->currentVersion->version_number)->toBe(2)
        ->and($page->version_number)->toBe(2)
        ->and($page->publishedRecord->version_number)->toBe(2)
        ->and($page->versions()->count())->toBe(2);
});

it('can rollback versions', function () {
    $page = Page::create(['title' => 'Home']);
    $page->update(['title' => 'Home 2']);

    expect($page->versions()->count())->toBe(2)
        ->and($page->currentVersion->version_number)->toBe(2);

    // rollback to version object
    $page->rollbackToVersion($page->versions->first());
    expect($page->currentVersion->version_number)->toBe(1)
        ->and($page->currentVersion->title)->toBe('Home');

    // rollback to version id
    $page->rollbackToVersion($page->versions->last()->id);
    expect($page->currentVersion->version_number)->toBe(2)
        ->and($page->currentVersion->title)->toBe('Home 2');

    // rollback to version number
    $page->RollbackToVersionNumber(1);
    expect($page->currentVersion->version_number)->toBe(1)
        ->and($page->currentVersion->title)->toBe('Home');
});

it('prunes old versions correctly with global config', function () {
    // no pruning
    config()->set('revisor.keep_versions', true);

    $page = Page::create(['title' => 'Home']);
    $page->update(['title' => 'Home 2']);
    $page->update(['title' => 'Home 3']);
    $page->update(['title' => 'Home 4']);

    expect($page->versions()->count())->toBe(4)
        ->and($page->currentVersion->version_number)->toBe(4);

    // prune n
    config()->set('revisor.keep_versions', 2);
    $page->update(['title' => 'Home 5']);
    expect($page->versions()->where('is_current', 0)->count())->toBe(2)
        ->and($page->currentVersion->version_number)->toBe(5);

    // prune all
    config()->set('revisor.keep_versions', false);
    $page->update(['title' => 'Home 6']);
    $page->refresh();

    expect($page->versions()->count())->toBe(0)
        ->and($page->version_number)->toBeNull()
        ->and($page->currentVersion)->toBeNull();

});
