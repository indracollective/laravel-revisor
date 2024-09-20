<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Indra\Revisor\Facades\Revisor;

it('creates and amends revisor schemas', function () {
    // table creation and amendments in TestCase.php

    // assert that expected tables exist
    expect(Schema::hasTable(Revisor::getDraftTableFor('pages')))->toBeTrue()
        ->and(Schema::hasTable(Revisor::getVersionTableFor('pages')))->toBeTrue()
        ->and(Schema::hasTable(Revisor::getPublishedTableFor('pages')))->toBeTrue();

    // define expected columns
    $expectedColumns = [
        'id',
        'title',
        'publisher_type',
        'publisher_id',
        'published_at',
        'is_current',
        'is_published',
        'content',
        'created_at',
        'updated_at',
        'version_number',
    ];

    sort($expectedColumns);

    // assert that expected columns exist for base table
    $actualColumns = Schema::getColumnListing('pages');
    sort($actualColumns);
    expect($expectedColumns)->toMatchArray($actualColumns);

    // assert that expected columns exist for versions table
    $actualColumns = Schema::getColumnListing(Revisor::getVersionTableFor('pages'));
    sort($actualColumns);
    $expectedVersionsColumns = array_merge($expectedColumns, ['record_id']);
    sort($expectedVersionsColumns);
    expect($expectedVersionsColumns)->toMatchArray($actualColumns);

    // assert that expected columns exist for published table
    $actualColumns = Schema::getColumnListing(Revisor::getPublishedTableFor('pages'));
    sort($actualColumns);
    expect($expectedColumns)->toMatchArray($actualColumns);
});
