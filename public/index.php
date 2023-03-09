<?php

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

use Noodlehaus\Config;
use Noodlehaus\Parser\Json;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use GuzzleHttp\Client as Curl;

require __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();

$container = new Container();

$container->set('config', function () {
    $file = sprintf('%1$s/../conf/%2$s.json', __DIR__, $_ENV['APP_ENV']);

    $content = (string)file_get_contents($file);

    $content = (string)preg_replace_callback(
        '/%([A-Z_]+)%/',
        function ($matches) {
            $key = $matches[1];
            if (array_key_exists($key, $_ENV)) {
                return $_ENV[$key];
            }
            return '%'.$matches[1].'%';
        },
        $content
    );

    $config = Config::load($content, new Json(), true);

    return $config;
});

$container->set('monolog', function () use ($container) {
    $logger = new Logger('bitbot');
    $logger->pushHandler(
        new StreamHandler(
            __DIR__ . '/../logs/debug.log',
            $container->get('config')->get('debug', false) ? Logger::DEBUG : Logger::WARNING
        )
    );

    return $logger;
});

$container->set('curl', function () {
    return new Curl(['connect_timeout' => 5, 'timeout' => 60]);
});

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write('Hello BitBot!');
    return $response;
});


if ($container->get('config')->get('network.facebook.enable', false)) {
    $app->get('/facebook', [\Bitbot\Network\Facebook::class, 'verifyToken']);
    $app->post('/facebook', [\Bitbot\Network\Facebook::class, 'main']);
}

if ($container->get('config')->get('network.telegram.enable', false)) {
    $app->post('/telegram', [\Bitbot\Network\Telegram::class, 'main']);
}

if ($container->get('config')->get('network.slack.enable', false)) {
    $app->get('/slack', [\Bitbot\Network\Slack::class, 'test']);
    $app->post('/slack', [\Bitbot\Network\Slack::class, 'verifyToken']);
}

$app->get('/privacy', function (Request $request, Response $response, array $args) {
    $response->getBody()->write('BitBot Privacy Policy<br/><br/>It\'s just an app for fun & laugh, so we will never collect any data about you.<br/>The message you send to us are never read or analyze because the answer we send to you is completly random.<br/>The only info we have for few milliseconds is a unique user_id send by the platform needed to answer you back.<br/>After the answer is sent, we do not have the info anymore.<br/>Have fun !<br/>-----<br/>The BitBot');
    return $response;
});

$app->run();
