<?php

declare(strict_types=1);

use Indra\Revisor\Tests\Models\Page;
use Indra\Revisor\Tests\Models\HiddenIdModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Indra\Revisor\Facades\Revisor;

it('publishes on created only when configured to do so', function () {
    $page = Page::create(['title' => 'Home']);
    $page->refresh();
    expect($page->is_published)->toBeFalse()
        ->and(Page::withPublishedContext()->find($page->id))->toBeNull();

    // global on
    config()->set('revisor.publishing.publish_on_created', true);
    $page = Page::create(['title' => 'Home 2']);

    $page->refresh();
    expect($page->is_published)->toBeTrue()
        ->and($page->publishedRecord->title)->toBe($page->title);

    // global off + instance on
    config()->set('revisor.publishing.publish_on_created', false);
    $page = Page::make(['title' => 'Home 3']);
    $page->publishOnCreated();
    $page->save();
    $page->refresh();
    expect($page->is_published)->toBeTrue()
        ->and($page->publishedRecord->title)->toBe($page->title);
});

it('publishes on updated only when configured to do so', function () {
    $page = Page::create(['title' => 'Home']);
    $page->refresh();
    expect($page->is_published)->toBeFalse()
        ->and(Page::withPublishedContext()->find($page->id))->toBeNull();

    // global on
    config()->set('revisor.publishing.publish_on_updated', true);
    $page = Page::create(['title' => 'Home 2']);
    $page->update(['title' => 'Home 3']);
    $page->refresh();
    expect($page->is_published)->toBeTrue()
        ->and($page->publishedRecord->title)->toBe($page->title);

    // global off + instance on
    config()->set('revisor.publishing.publish_on_updated', false);
    $page = Page::create(['title' => 'Home 4']);
    $page->publishOnUpdated();
    $page->update(['title' => 'Home 5']);
    $page->refresh();
    expect($page->is_published)->toBeTrue()
        ->and($page->publishedRecord->title)->toBe($page->title);
});

it('publishes and unpublishes a record when explicitly called', function () {

    $page = Page::create(['title' => 'Home']);

    // publish a record

    $page->publish();

    expect($page->is_published)->toBeTrue()
        ->and($page->published_at)->not()->toBeNull()
        ->and($page->publisher->id)->toBe($this->user->id);

    $published = Page::withPublishedContext()->find($page->id);

    expect($published)->toBeInstanceOf(Page::class)
        ->and($published->is_published)->toBeTrue()
        ->and($published->published_at)->not()->toBeNull()
        ->and($published->publisher->id)->toBe($this->user->id);

    // unpublish a record

    $page->unpublish();

    expect($page->is_published)->toBeFalse()
        ->and($page->published_at)->toBeNull()
        ->and($page->publisher)->toBeNull();

    $unpublished = Page::withPublishedContext()->find($page->id);
    expect($unpublished)->toBeNull();
});

it('syncs publishing metadata to version record when saving a new version', function () {
    $page = Page::create(['title' => 'Home']);

    // publish a record

    $page->publish();

    // get the version record and ensure it has the correct metadata
    $version = $page->currentVersionRecord;

    expect($version->is_published)->toBeTrue()
        ->and($version->published_at)->not()->toBeNull()
        ->and($version->publisher->id)->toBe($this->user->id);

    $page->saveNewVersion();

    expect($page->versionRecords()->firstWhere('is_current', 1)->is_published)->toBeFalse()
        ->and($page->versionRecords()->firstWhere('is_current', 0)->is_published)->toBeTrue();
});

it('does not double encoded json columns', function () {
    $jsonData = ['key' => 'value', 'nested' => ['foo' => 'bar']];

    $page = Page::create([
        'title' => 'Test Page',
        'metadata' => $jsonData,
    ])->publish();

    // Assert the JSON in the version matches the original
    expect($page->publishedRecord->metadata)->toBe($jsonData);
});

it('copies hidden attributes when publishing', function () {
    Revisor::createTableSchemas('hidden_id_models', function (Blueprint $table) {
        $table->id();
        $table->string('slug');
        $table->timestamps();
    });

    // Create two drafts and publish them in reverse order
    $recordA =  HiddenIdModel::create([ 'slug'=>'a' ]);
    $recordB =  HiddenIdModel::create([ 'slug'=>'b' ]);
    $recordB->publish();

    expect($recordB->publishedRecord)->not()->toBeNull();

    $recordA->publish();

    expect($recordB->slug)->toEqual($recordB->publishedRecord->slug);
});
