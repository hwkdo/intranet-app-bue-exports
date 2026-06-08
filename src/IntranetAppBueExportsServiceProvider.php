<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports;

use Hwkdo\IntranetAppBueExports\Services\ExportQueryBuilder;
use Hwkdo\IntranetAppBueExports\Services\ExportTypeAccessService;
use Hwkdo\IntranetAppBueExports\Services\StammdatenOptionsService;
use Livewire\Volt\Volt;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IntranetAppBueExportsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('intranet-app-bue-exports')
            ->hasConfigFile()
            ->hasViews()
            ->discoversMigrations();
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(ExportTypeAccessService::class);
        $this->app->singleton(ExportQueryBuilder::class);
        $this->app->singleton(StammdatenOptionsService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->mergeConfigFrom(
            __DIR__.'/../config/bue-exports-connection.php',
            'intranet-app-bue-exports.bue_connection'
        );

        $this->app->booted(function (): void {
            $cfg = config('intranet-app-bue-exports.bue_connection');

            if (is_array($cfg) && isset($cfg['name'])) {
                config()->set("database.connections.{$cfg['name']}", $cfg);
            }
        });

        $this->app->booted(function (): void {
            Volt::mount(__DIR__.'/../resources/views/livewire');
        });

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
