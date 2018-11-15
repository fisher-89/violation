<?php

declare(strict_types=1);

namespace Fisher\Schedule\Providers;

use App\Support\PackageHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Boorstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        // Register a database migration path.
        $this->loadMigrationsFrom($this->app->make('path.schedule.migrations'));

        // Register translations.
        $this->loadTranslationsFrom($this->app->make('path.schedule.lang'), 'schedule');

        // Publish config.
        $this->publishes([
            $this->app->make('path.schedule.config').'/schedule.php' => $this->app->configPath('schedule.php'),
        ], 'schedule-config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Bind all of the package paths in the container.
        $this->bindPathsInContainer();

        // Merge config.
        $this->mergeConfigFrom(
            $this->app->make('path.schedule.config').'/schedule.php',
            'schedule'
        );

        // register cntainer aliases
        $this->registerCoreContainerAliases();

        // Register singletons.
        $this->registerSingletions();

        // Register package handlers.
        $this->registerPackageHandlers();
    }

    /**
     * Bind paths in container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        foreach ([
            'path.schedule' => $root = dirname(dirname(__DIR__)),
            'path.schedule.config' => $root.'/config',
            'path.schedule.database' => $database = $root.'/database',
            'path.schedule.resources' => $resources = $root.'/resources',
            'path.schedule.lang' => $resources.'/lang',
            'path.schedule.migrations' => $database.'/migrations',
            'path.schedule.seeds' => $database.'/seeds',
        ] as $abstract => $instance) {
            $this->app->instance($abstract, $instance);
        }
    }

    /**
     * Register singletons.
     *
     * @return void
     */
    protected function registerSingletions()
    {
        // Owner handler.
        $this->app->singleton('schedule:handler', function () {
            return new \Fisher\Schedule\Handlers\PackageHandler();
        });

        // Develop handler.
        $this->app->singleton('schedule:dev-handler', function ($app) {
            return new \Fisher\Schedule\Handlers\DevPackageHandler($app);
        });
    }

    /**
     * Register the package class aliases in the container.
     *
     * @return void
     */
    protected function registerCoreContainerAliases()
    {
        foreach ([
            'schedule:handler' => [
                \Fisher\Schedule\Handlers\PackageHandler::class,
            ],
            'schedule:dev-handler' => [
                \Fisher\Schedule\Handlers\DevPackageHandler::class,
            ],
        ] as $abstract => $aliases) {
            foreach ($aliases as $alias) {
                $this->app->alias($abstract, $alias);
            }
        }
    }

    /**
     * Register package handlers.
     *
     * @return void
     */
    protected function registerPackageHandlers()
    {
        $this->loadHandleFrom('schedule', 'schedule:handler');
        $this->loadHandleFrom('schedule-dev', 'schedule:dev-handler');
    }

    /**
     * Register handler.
     *
     * @param string $name
     * @param \App\Support\PackageHandler|string $handler
     * @return void
     */
    private function loadHandleFrom(string $name, $handler)
    {
        PackageHandler::loadHandleFrom($name, $handler);
    }
}
