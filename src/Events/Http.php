<?php

namespace WorkerThink\Events;

use think\db\exception\PDOException;
use think\facade\App;
use think\facade\Config;
use think\facade\Db;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Timer;
use Workerman\Worker;
use WorkerThink\Monitor;
use WorkerThink\WorkerHttpApp;

/**
 * http的回调类
 * 实现了静态文件访问和API访问
 */
class Http
{

	/**
	 * 文件监听配置
	 */
	protected static array $monitorConfig = [];

	/**
	 * 等待响应的请求计数
	 */
	protected static int $waitResponseCount = 0;

	/**
	 * 初始 $_SERVER 数据
	 */
	protected static array $serverData;

	/**
	 * 需要绑定到 app 的类实例
	 */
	protected static array $bind = [];

	/**
	 * 构造函数
	 * 此函数内不能初始化资源(MySQL连接、Redis连接、Memcache连接等，比如不能使用Db)
	 */
	public function __construct()
	{
	}

	/**
	 * Worker子进程启动时的回调函数，每个子进程启动时都会执行。
	 */
	public function onWorkerStart(Worker $worker): void
	{
		self::$bind['worker'] = $worker;
		self::$serverData = $_SERVER;

		// 文件监听配置
		if (!self::$monitorConfig) {
			self::$monitorConfig = Config::get('worker_process.monitor.constructor.options');
		}

		// 初始化 Db 类单例，并在所有进程中共用
		if (!isset(self::$bind['db'])) {
			try {
				Db::execute("SELECT 1");
				$app = App::getInstance();
				self::$bind['db'] = $app->db;
				self::$bind['cache'] = $app->cache;
			} catch (PDOException) {
			}
		}

		if (0 == $worker->id) {
			new Monitor(self::$monitorConfig);
		}
	}

	/**
	 * 当客户端通过连接发来数据时(WorkerMan收到数据时)触发的回调函数
	 */
	public function onMessage(TcpConnection $connection, Request $request): void
	{
		$app = new WorkerHttpApp(root_path());
		foreach (self::$bind as $key => $item) {
			$app->$key = $item;
		}
		$app->init($connection, $request, self::$serverData);

		$path = $request->path() ?: '/';
		$file = public_path() . urldecode($path);

		if (!is_file($file)) {
			// 访问控制器

			self::$waitResponseCount++;
			if (self::$monitorConfig['soft_reboot'] && !Monitor::isPaused()) {
				Monitor::pause();
			}

			// 避免输出到命令行窗口
			while (ob_get_level() > 1) {
				ob_end_clean();
			}

			ob_start();

			$http = $app->http;
			$response = $http->run();
			$content = ob_get_clean();

			ob_start();
			$response->send();
			$app->http->end($response);
			$content .= ob_get_clean() ?: '';

			$connection->send(new Response($response->getCode(), $response->getHeader(), $content));

			self::$waitResponseCount--;
			if (self::$waitResponseCount <= 0 && self::$monitorConfig['soft_reboot']) {
				// 隔一次时间间隔再启动检测
				Timer::add(self::$monitorConfig['interval'], function () {
					if (Monitor::isPaused()) {
						Monitor::resume();
					}
				}, [], false);
			}
		} else {
			// 访问静态文件

			// 文件未修改，且存在 if-modified-since 则返回 304
			if (!empty($ifModifiedSince = $request->header('if-modified-since'))) {
				$modifiedTime = date('D, d M Y H:i:s', filemtime($file)) . ' ' . date_default_timezone_get();
				if ($modifiedTime === $ifModifiedSince) {
					$connection->send(new Response(304));
					return;
				}
			}

			$pathInfo = pathinfo($file);
			$response = (new Response())->withFile($file);

			// 已经检查过文件存在，无需担心后缀识别上的 /.（无后缀） 攻击
			if (!empty($pathInfo['extension'])) {
				$extension = strtolower($pathInfo['extension']);

				// 禁止访问的文件
				if (in_array($extension, Config::get('worker_http.staticServer.deny'))) {
					$connection->send(new Response(404));
					return;
				}

				// 要求浏览器下载而不是预览
				if (in_array($extension, Config::get('worker_http.staticServer.attachment'))) {
					$response->withHeader('Content-Disposition', "attachment; filename={$pathInfo['basename']}");
				}
			}

			// 文件修改过或没有 if-modified-since 头则发送文件
			$connection->send($response);
		}
	}

	/**
	 * 当客户端的连接上发生错误时触发。
	 */
	public function onError(TcpConnection $connection, $code, $msg)
	{
	}
}