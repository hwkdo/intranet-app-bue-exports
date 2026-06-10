<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppBueExports\Tests;

use Hwkdo\BueLaravel\BueLaravelServiceProvider;
use Hwkdo\IntranetAppBueExports\IntranetAppBueExportsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Livewire\Volt\VoltServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerWorkbenchModelAliases();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Hwkdo\\IntranetAppBueExports\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            BueLaravelServiceProvider::class,
            PermissionServiceProvider::class,
            LivewireServiceProvider::class,
            VoltServiceProvider::class,
            IntranetAppBueExportsServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $permissionMigrations = realpath(__DIR__.'/../vendor/spatie/laravel-permission/database/migrations');

        if ($permissionMigrations !== false) {
            $this->loadMigrationsFrom($permissionMigrations);
        }

        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('permission.models.role', Role::class);
        $app['config']->set('intranet-app-bue-exports.bue_connection.name', 'bue_exports_test');
    }

    private function registerWorkbenchModelAliases(): void
    {
        if (! class_exists(\App\Models\User::class)) {
            class_alias(User::class, \App\Models\User::class);
        }

        if (! class_exists(\App\Models\Role::class)) {
            class_alias(Role::class, \App\Models\Role::class);
        }
    }
}
