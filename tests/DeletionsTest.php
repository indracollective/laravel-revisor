<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\Tests\Models\Page;

beforeEach(function () {
    Revisor::getAllTablesFor('pages')->each(fn ($table) => DB::table($table)->truncate());
});

it('cascades deletions of draft records to the version and published records', function () {
    $page = Page::create(['title' => 'Home']);
    $page->publish();
    expect($page->publishedRecord->getTable())
        ->toBe($page->getPublishedTable())
        ->and($page->currentVersionRecord->getTable())
        ->toBe($page->getVersionTable());

    $page->delete();

    expect(Page::withPublishedContext()->find($page->id))->toBeNull()
        ->and(Page::withVersionContext()->firstWhere('record_id', $page->id))->toBeNull();
});

it('it marks the draft record and current version as unpublished when the published record is deleted', function () {
    $page = Page::create(['title' => 'Home']);

    $page->publish();
    expect($page->is_published)->toBeTrue();

    $page->publishedRecord->delete();
    $page->refresh();

    expect($page->is_published)->toBeFalse()
        ->and($page->currentVersionRecord?->is_published)->toBeFalse();
});

it('it removes the version number from published and draft records when version records are deleted', function () {
    $page = Page::create(['title' => 'Home']);
    $page->publish();

    expect($page->version_number)->toBe(1);

    $page->currentVersionRecord->delete();
    $page->refresh();

    expect($page->version_number)->toBeNull()
        ->and($page->publishedRecord?->version_number)->toBeNull();
});
