<?php

declare(strict_types=1);

use Indra\Revisor\Tests\Models\Page;

// These tests are concerned with functionality that involves
// both HasVersioing and HasPublishing traits together.

it('reverts published records as expected', function () {
    $page = Page::create(['title' => 'Home']);
    $page->update(['title' => 'Home 2']);
    $page->publish();

    expect($page->isPublished())->toBeTrue();

    $this->travel(1)->second();
    $page->revertToVersion(1);

    expect($page->isPublished())->toBeTrue()
        ->and($page->isRevised())->toBeTrue();
});
