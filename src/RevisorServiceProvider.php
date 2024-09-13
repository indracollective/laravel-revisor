<?php

namespace Indra\Revisor;

use Indra\Revisor\Commands\RevisorCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RevisorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-revisor')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_revisor_table')
            ->hasCommand(RevisorCommand::class);
    }
}
