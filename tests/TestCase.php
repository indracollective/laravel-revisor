<?php

namespace Indra\Revisor\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\RevisorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Indra\\Revisor\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            RevisorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $this->setUpDatabase();
    }

    protected function setUpDatabase()
    {
        // create tables
        Revisor::createTableSchemas('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        // amend tables
        Revisor::amendTableSchemas('pages', function (Blueprint $table): void {
            $table->string('content')->nullable();
        });
    }
}
