<?php

namespace WorkerThink\Command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use WorkerThink\Monitor;

class WorkerForWin extends Command
{
	protected function configure(): void
	{
		$this->setName('worker:win')
			->addArgument('server', Argument::REQUIRED, "The server to start，http|ws")
			->setDescription('Support hot update HTTP|WS service startup on Windows system through Workerman');
	}

	protected function execute(Input $input, Output $output): void
	{
		$server = trim($input->getArgument('server'));

		ini_set('display_errors', 'on');
		error_reporting(E_ALL);

		if (!in_array($server, ['http', 'ws'])) {
			$output->writeln("<error>Invalid argument server:$server, Expected http|ws .</error>");
			exit(1);
		}

		$servers = [];
		// $servers[] = sprintf('think worker %s start', strtolower($server));

		$runtimeProcessPath = $this->getRuntimeProcessPath();
		foreach (config('worker_process', []) as $processName => $config) {
			if ('process' === $processName) {
				continue;
			}

			array_unshift($servers, $this->write_process_file($runtimeProcessPath, $processName, ''));
		}

		$resource = $this->open_processes($servers);
		$this->monitor($resource, $servers);
	}

	protected function getRuntimeProcessPath(): string
	{
		$runtimeProcessPath = runtime_path('windows');
		if (!is_dir($runtimeProcessPath)) {
			mkdir($runtimeProcessPath);
		}

		return $runtimeProcessPath;
	}

	/**
	 * 监控文件变化，热更新
	 * @param $resource
	 * @param $servers
	 * @return void
	 */
	protected function monitor($resource, $servers): void
	{
		$options = config('worker_process.monitor.constructor.options', []);
		if (empty($options['switch'])) {
			return;
		}

		$monitor = new Monitor($options);
		while (true) {
			sleep(1);
			if ($monitor->checkAllFilesChange()) {
				$status = proc_get_status($resource);
				$pid = $status['pid'];

				shell_exec("taskkill /F /T /PID $pid");
				proc_close($resource);

				$resource = $this->open_processes($servers);
			}
		}
	}

	/**
	 * 创建新的进程执行命令
	 * @param $processFiles
	 * @return resource|void
	 */
	protected function open_processes($processFiles)
	{
		$pipes = [];
		$cmd = '"' . PHP_BINARY . '" ' . implode(' ', $processFiles);
		$descriptorSpec = [STDIN, STDOUT, STDOUT];
		$resource = proc_open($cmd, $descriptorSpec, $pipes, null, null, ['bypass_shell' => true]);
		if (!$resource) {
			exit("Can not execute $cmd\r\n");
		}

		return $resource;
	}

	protected function write_process_file($runtimeProcessPath, $processName, $firm): string
	{
		$processParam = $firm ? "plugin.$firm.$processName" : $processName;
		$configParam = $firm ? "config('$firm.process.$processName')" : "\$app->config->get('worker_process.$processName')";

		$fileContent = <<<EOF
<?php
namespace think;

require_once __DIR__ . '/../../vendor/autoload.php';

use Workerman\Worker;
use Workerman\Connection\TcpConnection;

ini_set('display_errors', 'on');
error_reporting(E_ALL);

if (is_callable('opcache_reset')) {
    opcache_reset();
}

try{
	\$app = new App();
	\$app->initialize();
			
	worker_start('$processParam', $configParam);
	
	if (DIRECTORY_SEPARATOR != "/") {
		Worker::\$logFile = \$app->config->get('worker_http.log_file') ?? Worker::\$logFile;
		TcpConnection::\$defaultMaxPackageSize = \$app->config->get('worker_http.max_package_size') ?? 10 * 1024 * 1024;
	}
	
	Worker::runAll();
}catch (\\Exception \$e){
	dump(\$e);
}
EOF;
		$processFile = sprintf("%sstart_%s.php", $runtimeProcessPath, $processName);
		file_put_contents($processFile, $fileContent);
		return $processFile;
	}
}