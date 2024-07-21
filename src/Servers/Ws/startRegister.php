<?php

use GatewayWorker\Register;

$config = config('worker_ws');

// 注册(Register)服务
new Register("text://{$config['register']['ip']}:{$config['register']['port']}");