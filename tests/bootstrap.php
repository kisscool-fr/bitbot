<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$container = new Container();
