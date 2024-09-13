<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Indra\Revisor\Facades\Revisor;

it('creates revisor schemas', function () {
    Revisor::schemaCreate('foo', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
    });

    expect(Schema::hasTable('foo'))->toBeTrue();
    expect(Schema::hasTable(Revisor::getVersionsTableNameFor('foo')))->toBeTrue();
    expect(Schema::hasTable(Revisor::getPublishedTableNameFor('foo')))->toBeTrue();

    $expectedColumns = [
        'id',
        'title',
        'is_current',
        'publisher_type',
        'publisher_id',
        'is_published',
        'published_at',
        'published_by',
    ];
    //expect([])->toMatchArray(Schema::getColumnListing('foo')); ?
    expect(Schema::hasColumns('foo', $expectedColumns))->toBeTrue();
    expect(Schema::hasColumns(Revisor::getVersionsTableNameFor('foo'), $expectedColumns + ['record_id']))->toBeTrue();
    expect(Schema::hasColumns(Revisor::getPublishedTableNameFor('foo'), $expectedColumns))->toBeTrue();
});
