<?php

namespace Indra\Revisor;

use Illuminate\Contracts\Http\Kernel;
use Indra\Revisor\Middleware\DraftMiddleware;
use Indra\Revisor\Middleware\PublishedMiddleware;
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
        $package->name('laravel-revisor')->hasConfigFile();
    }

    public function packageBooted(): void
    {
        // ensure the middlewares are registered before the SubstituteBindings middleware
        $this->app[Kernel::class]->prependToMiddlewarePriority(DraftMiddleware::class);
        $this->app[Kernel::class]->prependToMiddlewarePriority(PublishedMiddleware::class);
    }
}
