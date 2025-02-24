<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\Tests\Models\UlidModel;

it('creates and alters revisor schemas', function () {
    // table creation and alterments in TestCase.php

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
        'metadata',
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

it('plays nicely with ulid primary keys', function () {
    Revisor::createTableSchemas('ulid_models', function (Blueprint $table) {
        $table->ulid('id')->primary();
        $table->string('title');
        $table->timestamps();
    }, UlidModel::class);

    // Assert tables were created
    expect(Schema::hasTable('ulid_models_drafts'))->toBeTrue();
    expect(Schema::hasTable('ulid_models_versions'))->toBeTrue();
    expect(Schema::hasColumn('ulid_models_versions', 'record_id'))->toBeTrue();

    // Create a draft record with a ULID
    $ulidModel = UlidModel::create(['title' => 'Test Model']);
    expect($ulidModel->id)->not->toBeEmpty(); // Should have a ULID
    expect($ulidModel->currentVersionRecord->record_id)->toBe($ulidModel->id);
});
