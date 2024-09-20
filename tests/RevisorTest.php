<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Indra\Revisor\Enums\RevisorMode;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\Tests\Models\Page;

beforeEach(function () {
    Revisor::getAllTablesFor('pages')->each(fn ($table) => DB::table($table)->truncate());
});

it('respects revisor.default_mode config', function () {
    config()->set('revisor.default_mode', RevisorMode::Draft);
    $page = Page::create(['title' => 'Test Page']);
    $page->publish();

    $foundPage = Page::find($page->id);
    expect($foundPage->getTable())->toBe($page->getDraftTable());

    config()->set('revisor.default_mode', RevisorMode::Published);

    $foundPage = Page::find($page->id);
    expect($foundPage->getTable())->toBe($page->getPublishedTable());
});

it('respects explicit modes set on the RevisorInstance', function () {
    config()->set('revisor.default_mode', RevisorMode::Draft);
    $page = Page::create(['title' => 'Test Page']);
    $page->publish();

    $foundPage = Page::find($page->id);
    expect($foundPage->getTable())->toBe($page->getDraftTable());

    $foundPage = Revisor::withPublishedMode(fn () => Page::find($page->id));
    expect($foundPage->getTable())->toBe($page->getPublishedTable());

    Revisor::setMode(RevisorMode::Published);
    $foundPage = Page::first();
    expect($foundPage->getTable())->toBe($page->getPublishedTable());

    $foundPage = Revisor::withDraftMode(fn () => Page::first());
    expect($foundPage->getTable())->toBe($page->getDraftTable());
});
