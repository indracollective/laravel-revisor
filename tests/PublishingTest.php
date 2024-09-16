<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\Tests\Models\Page;
use Indra\Revisor\Tests\Models\User;

beforeEach(function () {
    Revisor::getAllTablesFor('pages')->each(fn ($table) => DB::table($table)->truncate());
});

it('does not publish a record on save when not configured to do so', function () {
    $page = Page::create(['title' => 'Homes']);
    $page->refresh();
    expect($page->is_published)->toBeFalse()
        ->and(Page::withPublishedTable()->find($page->id))->toBeNull();
});

it('publishes a record on save when configured to do so globally', function () {
    config()->set('revisor.publish_on_save', true);
    $page = Page::make(['title' => 'Homer']);
    $page->save();
    $page->refresh();

    expect($page->is_published)->toBeTrue()
        ->and($page->publishedRecord->id)->toBe($page->id)
        ->and($page->publishedRecord->title)->toBe($page->title);
});

it('publishes a record on save when configured to do so on the model', function () {
    $page = Page::make(['title' => 'Homer'])->publishOnSave();
    $page->save();
    $page->refresh();

    expect($page->is_published)->toBeTrue()
        ->and($page->publishedRecord->id)->toBe($page->id)
        ->and($page->publishedRecord->title)->toBe($page->title);
});

it('publishes and unpublishes a record when explicitly called', function () {
    $user = User::create(['email' => 'user@test.com']);
    $this->actingAs($user);

    $page = Page::create(['title' => 'Home']);

    // publish a record

    $page->publish();

    expect($page->is_published)->toBeTrue()
        ->and($page->published_at)->not()->toBeNull()
        ->and($page->publisher->id)->toBe($user->id);

    $published = Page::withPublishedTable()->find($page->id);

    expect($published)->toBeInstanceOf(Page::class)
        ->and($published->is_published)->toBeTrue()
        ->and($published->published_at)->not()->toBeNull()
        ->and($published->publisher->id)->toBe($user->id);

    // unpublish a record

    $page->unpublish();

    expect($page->is_published)->toBeFalse()
        ->and($page->published_at)->toBeNull()
        ->and($page->publisher)->toBeNull();

    $unpublished = Page::withPublishedTable()->find($page->id);
    expect($unpublished)->toBeNull();
});
