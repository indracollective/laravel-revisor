<?php

use Illuminate\Support\Facades\Route;
use Indra\Revisor\Enums\RevisorContext;
use Indra\Revisor\Middleware\DraftMiddleware;
use Indra\Revisor\Tests\Models\Page;

use function Pest\Laravel\get;

beforeEach(function () {
    test()->page1 = Page::create(['title' => 'Page 1']);
    test()->page2 = Page::create(['title' => 'Page 2'])->publish();

    config()->set('revisor.default_context', RevisorContext::Published);

    Route::middleware(['web'])->group(function () {
        Route::get('/default', function () {
            return Page::all();
        });

        Route::get('/with-drafts-middleware', function () {
            return Page::all();
        })->middleware(DraftMiddleware::class);

        Route::get('/with-drafts-middleware/{page}', function (Page $page) {
            return ['page' => $page];
        })->middleware(DraftMiddleware::class);
    });
});

it('doesnt include drafts by default', function () {
get('/default')->assertJsonCount(1);
    });

it('can use with draft middleware to include drafts on a route', function () {
get('/with-drafts-middleware')->assertJsonCount(2);
    });

it('can use with draft middleware to include drafts on a model binding', function () {
get('/with-drafts-middleware/'.test()->page1->id)
->assertJsonFragment(['title' => 'Page 1']);
    });
