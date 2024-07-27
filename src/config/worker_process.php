<?php

use WorkerThink\Monitor;

return [

	/**
	 * ---------------------------------------------------------------------
	 * think-queue队列支持
	 * 代替think-queue里的最后一步:监听任务并执行,无需另外起进程执行队列
	 * 如果enable属性设置为false时，需自行执行think-queue的监听已经守护进程执行
	 * ---------------------------------------------------------------------
	 */
	"queue" => [
		"enable" => true, // 是否开启队列监听并执行，true:开启，false:关闭
		"workers" => [
			// 键名是队列名称
			"default" => [
				"delay" => 0,  // 延迟执行时间，0为立即执行
				"sleep" => 3,
				"tries" => 0, // 队列执行失败后的重试次数
				"timeout" => 60,  // 进程执行超时时间
				"count" => 1,  // 进程数量
			],
		],
	],

	/**
	 * ---------------------------------------------------------------------
	 * 监控进程配置
	 * 自动监控进程的内容占用比例，到达阈值时自动执行进程reload平滑重启
	 * 以及监控文件变化，以达到热更新的目的
	 * ---------------------------------------------------------------------
	 */
	"monitor" => [
		'handler' => Monitor::class,
		'constructor' => [
			'options' => [
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
	],

	/**
	 * ---------------------------------------------------------------------
	 *
	 * 自定义进程配置
	 *
	 *  "demo" => [  // demo 为进程标识， 请勿以 process 为自定义进程名称
	 *         'handler' => Demo::class,  // 此处填写进程处理类，比如 定时任务 自动执行
	 *         'constructor' => [],         // 此处填写 handler 实例的 __constructor 函数接收的全部参数 实例化时会执行 解包
	 *         'count' => cpu_count(), // 设置当前Worker实例启动多少个进程，不设置时默认为1。
	 *         'user' => '',  // 设置当前Worker实例以哪个用户运行。此属性只有当前用户为root时才能生效。不设置时默认以当前用户运行。
	 *         'reusePort' => false, // 设置当前worker是否开启监听端口复用(socket的SO_REUSEPORT选项)。
	 *         'transport' => '', // 设置当前Worker实例所使用的传输层协议，目前只支持3种(tcp、udp、ssl)。不设置默认为tcp。
	 *         'protocol' => '', // 设置当前Worker实例的协议类。
	 *  ]
	 *
	 * ---------------------------------------------------------------------
	 */

];