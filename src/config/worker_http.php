<?php

$logFile = sprintf('%s%s.log', runtime_path('worker/log'), date('Y-m-d'));
if (!is_dir(dirname($logFile))) {
	mkdir(dirname($logFile), 0755, true);
}

return [
	'enable' => true, // 是否启用

	// Worker的参数（支持所有配置项）
	'option' => [
		'protocol' => 'http', // 协议，支持 tcp udp unix http websocket text
		'ip' => '0.0.0.0', // 监听地址
		'port' => '9501', // 监听端口
		'reusePort' => true,
		'name' => 'HttpWorker', // Worker实例名称
		'count' => cpu_count(), // 进程数
		'pidFile' => sprintf('%shttp.pid', runtime_path('worker')), // 进程ID存储位置
		'logFile' => $logFile, // 日志存储位置
	],

	// socket 上下文选项，可配置SSL证书等
	'context' => [],

	// 回调类命名空间
	'eventHandler' => 'WorkerThink\\Events\\Http',
	// 需要注册的回调方法
	'events' => [
		'onWorkerStart',
		'onWorkerReload',
		'onConnect',
		'onMessage',
		'onClose',
		'onBufferFull',
		'onBufferDrain',
		'onError',
	],

	// 静态服务器配置
	'staticServer' => [
		// 禁止访问的文件类型
		'deny' => ['php', 'bat', 'lock', 'ini'],
		// 要求浏览器下载而不是直接打开的文件类型（比如 pdf 文件内可能含有 xss 攻击代码）
		'attachment' => ['pdf'],
	],
];