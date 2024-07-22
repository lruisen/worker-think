<?php

namespace WorkerThink\Command;

use think\console\input\Argument;
use think\console\input\Option;
use think\console\Command;
use think\console\Input;
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
		$servers[] = sprintf('think worker:start %s', strtolower($server));

		// TODO 将来可能加入新的服务需要直接处理

		$resource = $this->open_processes($servers);
		$this->monitor($resource, $servers);
	}

	/**
	 * 监控文件变化，热更新
	 * @param $resource
	 * @param $servers
	 * @return void
	 */
	protected function monitor($resource, $servers): void
	{
		$options = config('worker_process.monitor.constructor', []);
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
}