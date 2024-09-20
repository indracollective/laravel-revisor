<?php

namespace Indra\Revisor\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Indra\Revisor\Enums\RevisorMode;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\RevisorServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;

#[WithMigration]
class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Indra\\Revisor\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            RevisorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('revisor.default_mode', RevisorMode::Draft);
        config()->set('app.key', Str::random(32));
        $this->setUpDatabase();
    }

    protected function setUpDatabase(): void
    {
        // create users table
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });

        // create test tables
        Revisor::createTableSchemas('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        // amend test tables
        Revisor::amendTableSchemas('pages', function (Blueprint $table): void {
            $table->string('content')->nullable();
        });
    }
}
