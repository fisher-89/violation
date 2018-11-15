<?php

declare(strict_types=1);

namespace Fisher\Schedule\Providers;

use Illuminate\Support\ServiceProvider;
use Fisher\Schedule\Services\DingtalkManager;

class DingServiceProvider extends ServiceProvider
{
    /**
     * 是否延时加载提供器。
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(DingtalkManager::class, function ($app) {
            return new DingtalkManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [DingtalkManager::class];
    }

}