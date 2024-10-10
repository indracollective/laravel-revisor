<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Indra\Revisor\Enums\RevisorContext;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\Tests\Models\Page;

beforeEach(function () {
    Revisor::getAllTablesFor('pages')->each(fn ($table) => DB::table($table)->truncate());
});

it('respects revisor.default_context config', function () {
    config()->set('revisor.default_context', RevisorContext::Draft);
    $page = Page::create(['title' => 'Test Page']);
    $page->publish();

    $foundPage = Page::find($page->id);
    expect($foundPage->getTable())->toBe($page->getDraftTable());

    config()->set('revisor.default_context', RevisorContext::Published);

    $foundPage = Page::find($page->id);

    expect($foundPage->getTable())->toBe($page->getPublishedTable());
});

it('respects RevisorContext set on the Laravel Context', function () {
    config()->set('revisor.default_context', RevisorContext::Draft);
    $page = Page::create(['title' => 'Test Page']);
    $page->publish();

    $foundPage = Page::find($page->id);
    expect($foundPage->getTable())->toBe($page->getDraftTable());

    $foundPage = Revisor::withPublishedContext(fn () => Page::find($page->id));
    expect($foundPage->getTable())->toBe($page->getPublishedTable());

    Revisor::setContext(RevisorContext::Published);
    $foundPage = Page::first();
    expect($foundPage->getTable())->toBe($page->getPublishedTable());

    $foundPage = Revisor::withDraftContext(fn () => Page::first());
    expect($foundPage->getTable())->toBe($page->getDraftTable());
});
