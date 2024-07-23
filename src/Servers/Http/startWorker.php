<?php

use Workerman\Worker;

$config = config('worker_http');
$listen = sprintf('%s://%s:%s', $config['option']['protocol'] ?? 'http', $config['option']['ip'] ?? '0.0.0.0', $config['option']['port'] ?? '8000');

/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
$worker = new Worker($listen, $config['context']);

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