<?php

use think\App;
use Workerman\Worker;

if (!function_exists('worker_start')) {
	/**
	 * 开启 worker 进程
	 * @param string $process 进程名称
	 * @param array $config 进程配置
	 * @return void
	 */
	function worker_start(string $process, array $config): void
	{
		$worker = new Worker($config['listen'] ?? null, $config['context'] ?? []);

		$propertyMap = ['count', 'user', 'group', 'reloadable', 'reusePort', 'transport', 'protocol'];
		$worker->name = $process;
		foreach ($propertyMap as $property) {
			if (isset($config[$property])) {
				$worker->$property = $config[$property];
			}
		}

		$worker->onWorkerStart = function ($worker) use ($config) {
			$app = new App();
			$app->initialize();

			if (!isset($config['handler'])) {
				return;
			}

			if (!class_exists($config['handler'])) {
				echo "process error: class {$config['handler']} not exists\r\n";
				return;
			}

			$instance = $app->make($config['handler'], $config['constructor'] ?? []);
			worker_bind($worker, $instance);
		};
	}
}

if (!function_exists('worker_bind')) {
	/**
	 * worker 进程绑定 回调属性
	 * @param Worker $worker
	 * @param mixed $class
	 * @return void
	 */
	function worker_bind(Worker $worker, mixed $class): void
	{
		$callbackMap = [
			'onConnect',
			'onMessage',
			'onClose',
			'onError',
			'onBufferFull',
			'onBufferDrain',
			'onWorkerStop',
			'onWebSocketConnect',
			'onWorkerReload'
		];

		foreach ($callbackMap as $name) {
			if (method_exists($class, $name)) {
				$worker->$name = [$class, $name];
			}
		}

		if (method_exists($class, 'onWorkerStart')) {
			call_user_func([$class, 'onWorkerStart'], $worker);
		}
	}
}