<?php
// web/index.php
require_once __DIR__ . '/../vendor/autoload.php';


use Symfony\Component\HttpFoundation\Response;
use Lokhman\Silex\Provider as ToolsProviders;

date_default_timezone_set("Europe/Paris");

$dotenv = Dotenv\Dotenv::create(__DIR__ . '/../');
$dotenv->load();


$app = new Silex\Application();

// Services
$app->register(new ToolsProviders\ConfigServiceProvider(), [
    'config.dir' => __DIR__ . '/../conf'
]);
$app->register(new Silex\Provider\MonologServiceProvider(), [
    'monolog.logfile' => __DIR__ . '/../logs/debug.log',
    'monolog.level' => $app['debug'] ? 'DEBUG' : 'WARNING',
    'monolog.name' => 'bitbot'
]);
$app['curl'] = new Curl\Curl();


// Routing
$app->get('/', function () {
    return new Response('Hello BitBot !', 200);
});

if ($app['config']['network']['facebook']['enable']) {
    $app->get('/facebook', 'Bitbot\\Network\\Facebook::verifyToken');
    $app->post('/facebook', 'Bitbot\\Network\\Facebook::main');    
}

if ($app['config']['network']['slack']['enable']) {
    $app->get('/slack', 'Bitbot\\Network\\Slack::test');
    $app->post('/slack', 'Bitbot\\Network\\Slack::verifyToken');
}

if ($app['config']['network']['telegram']['enable']) {
    $app->post('/telegram', 'Bitbot\\Network\\Telegram::main');
}

$app->get('/privacy', function () {
    return new Response('BitBot Privacy Policy<br/><br/>It\'s just an app for fun & laugh, so we will never collect any data about you.<br/>The message you send to us are never read or analyze because the answer we send to you is completly random.<br/>The only info we have for few milliseconds is a unique user_id send by the platform needed to answer you back.<br/>After the answer is sent, we do not have the info anymore.<br/>Have fun !<br/>-----<br/>The BitBot', 200);
});

// Run !
$app->run();
