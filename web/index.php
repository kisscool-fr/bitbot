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

$app->get('/privacy', function () {
    return new Response('BitBot Privacy Policy<br/><br/>It\'s just an app for fun & laugh, so we will never collect any data about you.<br/>The message you send to us are never read or analyze because the answer we send to you is completly random.<br/>The only info we have for few milliseconds is a unique user_id send by the platform needed to answer you back.<br/>After the answer is sent, we do not have the info anymore.<br/>Have fun !<br/>-----<br/>The BitBot', 200);
});

// Run !
$app->run();