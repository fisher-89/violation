<?php

declare(strict_types=1);

namespace Fisher\Schedule\Admin\Controllers;

class HomeController
{
    public function index()
    {
        return trans('schedule::messages.success');
    }
}