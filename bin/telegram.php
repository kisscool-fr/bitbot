<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($argv[1])) {
    die('Usage: ' . $argv[0] . ' <webhook_url|delete>' . PHP_EOL);
}

Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();

if (!array_key_exists('TELEGRAM_TOKEN', $_ENV)) {
    die('Missing TELEGRAM_TOKEN environment variable' . PHP_EOL);
}

$url = $argv[1];
$delete = ($url == 'delete');

$parameters = ['url' => ($delete ? '' : $url)];

$url = 'https://api.telegram.org/bot' . $_ENV['TELEGRAM_TOKEN'] . '/setWebhook';

$curl = new GuzzleHttp\Client(['connect_timeout' => 5, 'timeout' => 60]);
$response = $curl->get($url, ['query' => $parameters]);

var_dump($response->getBody());
