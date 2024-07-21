<?php

namespace WorkerThink\Command;

use WorkerThink\Monitor;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class WorkerHttpForWinHpm extends Command
{
    protected function configure(): void
    {
        $this->setName('worker:http')->setDescription('Use worker to start HTTP service and support hot updates');
    }

    protected function execute(Input $input, Output $output): void
    {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        $runtimeProcessPath = runtime_path('windows');
        if (!is_dir($runtimeProcessPath)) {
            mkdir($runtimeProcessPath);
        }

        $processFiles = [
            "think worker:http"
        ];

        foreach (config('worker_process', []) as $processName => $config) {
            # $processFiles[] = $this->write_process_file($runtimeProcessPath, $processName, '');
        }

        $resource = $this->open_processes($processFiles);

        // 启动文件监听
        $monitor = new Monitor(config('worker_process.monitor.constructor'));
        while (true) {
            sleep(1);
            if ($monitor->checkAllFilesChange()) {
                $status = proc_get_status($resource);
                $pid = $status['pid'];

                shell_exec("taskkill /F /T /PID $pid");
                proc_close($resource);

                $resource = $this->open_processes($processFiles);
            }
        }

    }

    protected function open_processes($servers)
    {
        $pipes = [];
        $cmd = sprintf('%s %s', PHP_BINARY, implode(' ', $servers));
        $descriptorSpec = [STDIN, STDOUT, STDOUT];
        $resource = proc_open($cmd, $descriptorSpec, $pipes, null, null, ['bypass_shell' => true]);
        if (!$resource) {
            exit("Can not execute $cmd\r\n");
        }

        return $resource;
    }

    protected  function write_process_file($runtimeProcessPath, $processName, $firm): string
    {
        # $processParam = $firm ? "$processName" : $processName;
        # $configParam = $firm ? "config('plugin.$firm.worker_process')['$processName']" :"config('worker_process')['$processName']";

        $processParam = $processName;
        $configParam = "Config::get('worker_process')['$processName']['constructor']";
        $fileContent = <<<EOF
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use think\\facade\Config;

ini_set('display_errors', 'on');
error_reporting(E_ALL);

if (is_callable('opcache_reset')) {
    opcache_reset();
}

worker_start('$processParam', $configParam);

if (DIRECTORY_SEPARATOR != "/") {
    Worker::\$logFile = Config::get('worker_http')['option']['log_file'] ?? Worker::\$logFile;
    TcpConnection::\$defaultMaxPackageSize = Config::get('worker_http')['option']['max_package_size'] ?? 10 * 1024 * 1024;
}

Worker::runAll();

EOF;

        $processFile = $runtimeProcessPath . "start_$processParam.php";
        file_put_contents($processFile, $fileContent);
        return $processFile;
    }
}