<?php

namespace WorkerThink;

use WorkerThink\Command\WorkerHttpForWinHpm;
use WorkerThink\Command\WorkerHttpForWin;

class Service extends \think\Service
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        $this->commands([
            WorkerHttpForWinHpm::class,
            WorkerHttpForWin::class,
        ]);
    }
}