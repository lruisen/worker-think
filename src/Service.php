<?php

namespace WorkerThink;

use WorkerThink\Command\Worker;
use WorkerThink\Command\WorkerStartForWin;
use WorkerThink\Command\WorkerForWin;

class Service extends \think\Service
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        defined('__WT_PKG__') or define('__WT_PKG__', __DIR__);

        $this->commands([
            Worker::class,
            WorkerForWin::class,
            WorkerStartForWin::class,
        ]);
    }
}