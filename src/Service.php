<?php

namespace WorkerThink;

use WorkerThink\Command\Worker;
use WorkerThink\Command\WorkerHttpForWinHpm;
use WorkerThink\Command\WorkerHttpForWin;

class Service extends \think\Service
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        define('PKG_PATH', __DIR__);

        $this->commands([
            WorkerHttpForWinHpm::class,
            WorkerHttpForWin::class,
            Worker::class,
        ]);
    }
}