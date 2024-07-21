<?php

namespace WorkerThink\Command;

use think\console\input\Argument;
use think\helper\Str;
use Workerman\Worker;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class WorkerStartForWin extends Command
{
    protected function configure(): void
    {
        $this->setName('worker:start')
            ->addArgument('server', Argument::REQUIRED, "The server to start，http|ws")
            ->setDescription('Starting HTTP|WS Service on Windows System through Workerman');
    }

    protected function execute(Input $input, Output $output): void
    {
        $server = trim($input->getArgument('server'));

        if (!in_array($server, ['http', 'ws'])) {
            $output->writeln("<error>Invalid argument server:$server, Expected http|ws .</error>");
            exit(1);
        }


        $serverPath = sprintf("%s/Servers/%s/start*.php", __WT_PKG__, Str::studly($server));
        $startFiles = glob($serverPath);
        if (!$startFiles) {
            $output->writeln("<error>$server server does not exist.</error>");
            exit(1);
        }

        foreach (glob($serverPath) as $startFile) {
            include_once $startFile;
        }

        Worker::runAll();
    }
}