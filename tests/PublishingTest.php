<?php

declare(strict_types=1);

use Indra\Revisor\Tests\Models\Page;

it('publishes records as expected', function () {
    $page = Page::create(['title' => 'Home']);
    $page->refresh();

    expect($page->is_published)->toBe(false);

    $page->publish();
    expect($page->is_published)->toBe(true);

    $published = Page::withPublishedTable()->where('id', $page->id)->ddRawSQL();
    dd($published);

});
