<?php

namespace Indra\Revisor\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Indra\Revisor\Enums\RevisorContext;
use Indra\Revisor\Facades\Revisor;
use Indra\Revisor\RevisorServiceProvider;
use Indra\Revisor\Tests\Models\User;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;

#[WithMigration]
class TestCase extends Orchestra
{
    public ?User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Indra\\Revisor\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        $this->actingAs($this->user);
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
        config()->set('revisor.default_context', RevisorContext::Draft);
        config()->set('app.key', Str::random(32));
        $this->setUpDatabase();
    }

    protected function setUpDatabase(): void
    {
        // create users table
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
            $table->timestamps();
        });

        // create test tables
        Revisor::createTableSchemas('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // alter test tables
        Revisor::alterTableSchemas('pages', function (Blueprint $table): void {
            $table->string('content')->nullable();
        });
    }
}
