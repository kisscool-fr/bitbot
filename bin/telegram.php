<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conf/local.conf.php';
require_once __DIR__ . '/../conf/default.conf.php';

$curl = new Curl\Curl();

if (!isset($argv[1]))
{
    die('Usage: ' . $argv[0] . ' <webhook_url|delete>' . PHP_EOL);
}

$url = $argv[1];
$delete = ($url == 'delete');

$parameters = ['url' => ($delete ? '' : $url)];

$url = 'https://api.telegram.org/bot' . TELEGRAM_TOKEN . '/setWebhook?' . http_build_query($parameters);

$curl->setopt(CURLOPT_RETURNTRANSFER, true);
$curl->setopt(CURLOPT_CONNECTTIMEOUT, 5);
$curl->setopt(CURLOPT_TIMEOUT, 60);
$curl->get($url);
var_dump($curl->response);