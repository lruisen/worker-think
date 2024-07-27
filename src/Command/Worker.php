<?php

namespace WorkerThink\Command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\helper\Str;
use Workerman\Worker as WorkerManWorker;

class Worker extends Command
{
	protected function configure(): void
	{
		$this->setName('worker')
			->addArgument('server', Argument::REQUIRED, "The server to start. http|ws")
			->addArgument('action', Argument::REQUIRED, "start|stop|restart|reload|status")
			->addOption('daemon', 'd', Option::VALUE_NONE, 'Start in daemon mode.')
			->setDescription('Starting HTTP|WS Service on Linux System through Workerman');
	}

	protected function execute(Input $input, Output $output): void
	{
		$action = trim($input->getArgument('action'));
		$server = trim($input->getArgument('server'));

		ini_set('display_errors', 'on');
		error_reporting(E_ALL);

		// 检查参数
		$this->checkParameters($output, $server, $action);

		// 检查扩展
		$this->checkExtensions($output);

		// worker 主进程重载时重新 编译并缓存 PHP 脚本
		$this->setMasterReload();

		// 加载对应的请求
		$serverPath = sprintf("%s/Servers/%s/start*.php", __WT_PKG__, Str::studly($server));
		$startFiles = glob($serverPath);
		if (!$startFiles) {
			$output->writeln("<error>$server server does not exist.</error>");
			exit(1);
		}

		foreach (glob($serverPath) as $startFile) {
			require_once $startFile;
		}

		// Windows does not support custom processes.
		$this->loadAllProcesses();

		WorkerManWorker::runAll();
	}

	protected function checkParameters(Output $output, $server, $action): void
	{
		if (!in_array($server, ['http', 'ws'])) {
			$output->writeln("<error>Invalid argument server:$server, Expected http|ws .</error>");
			exit(1);
		}

		if (!in_array($action, ['start', 'stop', 'restart', 'reload', 'status'])) {
			$output->writeln("<error>Invalid argument action:$action, Expected start|stop|restart|reload|status .</error>");
			exit(1);
		}
	}

	/**
	 * 检测扩展是否安装
	 * @param Output $output
	 * @return void
	 */
	protected function checkExtensions(Output $output): void
	{
		// Windows 系统跳转检查
		if (str_starts_with(strtolower(PHP_OS), 'win') || DIRECTORY_SEPARATOR === '\\') {
			return;
		}

		if (!extension_loaded('pcntl')) {
			$output->writeln("<error>Please install pcntl extension. See https://doc.workerman.net/appendices/install-extension.html </error>");
			exit(1);
		}

		if (!extension_loaded('posix')) {
			$output->writeln("<error>Please install posix extension. See https://doc.workerman.net/appendices/install-extension.html </error>");
			exit(1);
		}
	}

	/**
	 * 主进程收到重载信号 时编译并缓存 PHP 脚本
	 * @return void
	 */
	protected function setMasterReload(): void
	{
		WorkerManWorker::$onMasterReload = function () {
			if (!function_exists('opcache_get_status')) {
				return;
			}

			if (!$status = opcache_get_status()) {
				return;
			}

			if (isset($status['scripts']) && $scripts = $status['scripts']) {
				foreach (array_keys($scripts) as $file) {
					opcache_invalidate($file, true);
				}
			}
		};
	}

	/**
	 * 加载所有的任务进程
	 * @return void
	 */
	protected function loadAllProcesses(): void
	{
		if (str_starts_with(strtolower(PHP_OS), 'win') || DIRECTORY_SEPARATOR === '\\') {
			return;
		}

		foreach (config('worker_process') as $process_name => $config) {
			if ('queue' === $process_name) {
				continue;
			}

			worker_start($process_name, $config);
		}
	}
}