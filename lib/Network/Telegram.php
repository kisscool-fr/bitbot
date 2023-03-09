<?php

namespace Bitbot\Network;

use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

use Bitbot\NetworkInterface;

class Telegram implements NetworkInterface
{
    private Container $container;
    private string $endpoint;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function main(Request $request, Response $response, array $args): Response
    {
        $this->endpoint = sprintf(
            'https://api.telegram.org/bot%s/',
            $this->container->get('config')->get('network.telegram.token')
        );

        /** @var array<string, array<string, string>> $messages */
        $messages = $this->decode();

        $this->container->get('monolog')->debug('count messages:'.count($messages));

        if (count($messages) == 0) {
            $response->getBody()->write('');
            return $response;
        }

        $this->process($messages);

        $response->getBody()->write('');
        return $response;
    }

    public function decode(): array
    {
        $input = file_get_contents('php://input');

        $this->container->get('monolog')->debug($input);

        $data = json_decode((string)$input, true);

        $messages = [];

        if (is_array($data) && array_key_exists('message', $data)) {
            $message = $data['message'];

            $datas = [];
            $datas['chat_id'] = $message['chat']['id'];

            $messages[] = $datas;
        }

        $this->container->get('monolog')->debug(json_encode($messages));

        return $messages;
    }

    /**
     * @param array<string, array<string, string>> $messages
     */
    public function process(array $messages): void
    {
        foreach ($messages as $message) {
            $this->sendRandomAnswer($message['chat_id']);
        }
    }

    public function sendAPIRequestJson(string $method, array $parameters): string|bool
    {
        if (!is_string($method)) {
            return false;
        }

        if (!$parameters) {
            $parameters = array();
        } elseif (!is_array($parameters)) {
            return false;
        }

        try {
            $response = $this->container->get('curl')->post(
                $this->endpoint,
                ['json' => array_merge(['method' => $method], $parameters['message'])]
            );
            return $response->getBody();
        } catch (ClientException $e) {
            $exception = $e->getResponse();

            $this->container->get('monolog')->error(
                sprintf(
                    '%d:%s',
                    $exception->getStatusCode(),
                    $exception->getReasonPhrase(),
                )
            );
            return Psr7\Message::toString($exception);
        }
    }

    public function sendRandomAnswer(string $chat_id): void
    {
        $answer = (mt_rand(0, 1) > 0.5) ? 'Yes.' : 'No.';

        $this->sendAPIRequestJson('sendMessage', ['message' => [
            'chat_id' => $chat_id,
            'text' => $answer,
            'parse_mode' => 'Markdown'
        ]]);
    }
}
