<?php

set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';

use studio24\Rotate\Rotate;

$rotate = new Rotate(__DIR__ . '/../logs/*.log');
$rotate->keep(10);
$rotate->run();
