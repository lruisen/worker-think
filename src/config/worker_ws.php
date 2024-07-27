<?php

$logFile = sprintf('%s%s.log', runtime_path('worker/log'), date('Y-m-d'));
if (!is_dir(dirname($logFile))) {
	mkdir(dirname($logFile), 0755, true);
}

$register = [
	'ip' => '127.0.0.1',
	'port' => '1260',
];

return [
	'enable' => false, // 是否启用
	// 注册(Register)服务参数
	'register' => $register,

	// 网关(Gateway)服务参数
	'gateway' => [
		'protocol' => 'websocket', // 协议，支持 websocket text frame tcp
		'ip' => '0.0.0.0', // 监听地址
		'port' => '2828', // 监听端口
		'name' => 'WebSocketGateway', // 进程名称
		'count' => cpu_count(), // 进程数
		'lanIp' => '127.0.0.1',
		'startPort' => 1360,
		'pingInterval' => 55,
		'pingNotResponseLimit' => 1,
		'pingData' => '',
		'registerAddress' => "{$register['ip']}:{$register['port']}",
	],

	// 业务(Business)服务参数
	'business' => [
		'name' => 'WebSocketBusiness',
		'count' => cpu_count(),
		'eventHandler' => 'WorkerThink\\Events\\WsBusiness',
		'registerAddress' => "{$register['ip']}:{$register['port']}",
	],

	// Worker的参数（支持所有配置项）
	'option' => [
		'pidFile' => sprintf('%sws.pid', runtime_path('worker')), // 进程ID存储位置
		'logFile' => $logFile, // 日志存储位置
	],

	// 网关(Gateway)上下文选项
	'gatewayContext' => [],
];