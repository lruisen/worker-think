<?php

use GatewayWorker\BusinessWorker;

$config = config('worker_ws');

/** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
$business = new BusinessWorker();

// 设置 Business 参数
foreach ($config['business'] as $key => $value) {
    $business->$key = $value;
}