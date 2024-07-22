<?php

use WorkerThink\Monitor;

return [
	"monitor" => [
		'handler' => Monitor::class,
		'constructor' => [
			'switch' => env('APP_DEBUG', false), // 是否开启PHP文件更改监控（调试模式下自动开启）
			'interval' => 2, // 文件监控检测时间间隔（秒）
			'soft_reboot' => true, // 在没有请求时（空闲）时才检测，仅 http 服务下有效
			'paths' => [
				app_path(),
				config_path(),
				root_path('vendor/composer'),
				root_path('route'),
			], // 文件监控目录
			'extensions' => ['php', 'env'], // 监控的文件类型

			/**
			 * 以下为内存监控配置（仅 Linux 系统，Win 和 Mac 均不支持）
			 * 当达到 memory_limit 时，进程将自动重启以避免内存泄露
			 * 若需手动配置以下的 memory_limit，请确保其值小于 ini_get('memory_limit')，并留有一定余地，以避免 Allowed memory size of XXX bytes exhausted
			 */
			// 'memory_limit' => '102M', // 默认取值为 ini_get('memory_limit') 的 80%，你也可以手动配置，单位可以为：G、M、K
			'memory_monitor_interval' => 60, // 内存检测时间间隔（秒）
		]
	]
];