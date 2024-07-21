<?php

use Workerman\Worker;
use GatewayWorker\Gateway;

$config = config('worker_ws');

/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
$gateway = new Gateway("{$config['gateway']['protocol']}://{$config['gateway']['ip']}:{$config['gateway']['port']}", $config['gatewayContext']);

// 避免pid混乱
$config['option']['pidFile'] .= '_' . $config['gateway']['port'];

// worker 参数设定
if (!empty($config['option'])) {
    foreach ($config['option'] as $key => $value) {
        if (in_array($key, ['stdoutFile', 'daemonize', 'pidFile', 'logFile'])) {
            Worker::${$key} = $value;
        } else {
            $gateway->$key = $value;
        }
    }
}

// gateway 参数设定
if (!empty($config['gateway'])) {
    foreach ($config['gateway'] as $key => $value) {
        if (!in_array($key, ['protocol', 'ip', 'port'])) {
            $gateway->$key = $value;
        }
    }
}