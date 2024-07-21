<?php

namespace WorkerThink\Command;

use Workerman\Worker;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class WorkerHttpForWin extends Command
{
    protected function configure(): void
    {
        $this->setName('worker:http')->setDescription('Worker for windows server start');
    }

    protected function execute(Input $input, Output $output): void
    {
        $config = config('worker_http');

        /** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
        $worker = new Worker($config['option']['protocol'] . '://' . $config['option']['ip'] . ':' . $config['option']['port'], $config['context']);

        // 避免pid混乱
        $config['option']['pidFile'] .= '_' . $config['option']['port'];

        // Worker 参数设定
        foreach ($config['option'] as $key => $value) {
            if (in_array($key, ['protocol', 'ip', 'port'])) continue;

            if (in_array($key, ['stdoutFile', 'daemonize', 'pidFile', 'logFile'])) {
                Worker::${$key} = $value;
            } else {
                $worker->$key = $value;
            }
        }

        if (class_exists($config['eventHandler'])) {
            $eventHandler = new $config['eventHandler']();

            // 设定回调
            foreach ($config['events'] as $event) {
                if (method_exists($eventHandler, $event)) {
                    $worker->$event = [$eventHandler, $event];
                }
            }
        }

        Worker::runAll();
    }
}