#!/usr/bin/env php
<?php

namespace think;

require_once __DIR__ . '/../../../autoload.php';

$app = new App();

$app->console->call('worker', [
	'http',
	'start'
]);