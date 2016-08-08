<?php
// web/index.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conf/local.conf.php';
require_once __DIR__ . '/../conf/default.conf.php';

use Symfony\Component\HttpFoundation\Response;


$app = new Silex\Application();

// Config
$app['debug'] = DEBUG;

// Services
$app['curl'] = new Curl\Curl();
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__ . '/../logs/debug.log',
    'monolog.level' => DEBUG ? 'DEBUG' : 'INFO',
    'monolog.name' => 'bitbot'
));

// Routing
$app->get('/', function () {
    return new Response('Hello BitBot !', 200);
});

$app->get('/facebook', 'Bitbot\\Network\\Facebook::verifyToken');
$app->post('/facebook', 'Bitbot\\Network\\Facebook::main');

$app->post('/telegram', 'Bitbot\\Network\\Telegram::main');

// Run !
$app->run();