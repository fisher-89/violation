<?php

namespace Fisher\Schedule\Services;

use Illuminate\Support\Manager;
use Fisher\Schedule\Services\Contracts\Dingtalk;

class DingtalkManager extends Manager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Create a new Cache manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Create dingding auth Driver.
     *
     * @return \Fisher\Schedule\Services\Contracts\Dingtalk
     */
    public function createAuthDriver(): Dingtalk
    {
        return $this->app->make(Drivers\AuthDriver::class);
    }

    /**
     * Create dingding user Driver.
     *
     * @return \Fisher\Schedule\Services\Contracts\Dingtalk
     */
    public function createUserDriver(): Dingtalk
    {
        return $this->app->make(Drivers\UserDriver::class);
    }

    /**
     * Create dingding attendance Driver.
     *
     * @return \Fisher\Schedule\Services\Contracts\Dingtalk
     */
    public function createAttendanceDriver(): Dingtalk
    {
        return $this->app->make(Drivers\AttendanceDriver::class);
    }

    /**
     * Create dingding process Driver.
     *
     * @return \Fisher\Schedule\Services\Contracts\Dingtalk
     */
    public function createProcessDriver(): Dingtalk
    {
        return $this->app->make(Drivers\ProcessDriver::class);
    }

    /**
     * Get the default cache driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        throw new \Exception('不允许使用默认驱动，必须选择驱动并进行使用');
    }

}
