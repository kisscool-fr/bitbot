<?php

namespace Bitbot\Network;

use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

use Bitbot\NetworkInterface;

class Facebook implements NetworkInterface
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, string> $args
     * @return Response
     */
    public function verifyToken(Request $request, Response $response, array $args): Response
    {
        $query_string = $request->getQueryParams();

        if (
            array_key_exists('hub_verify_token', $query_string) &&
            $query_string['hub_verify_token'] == $this->container->get('config')->get('network.facebook.app_token')
        ) {
            $response->getBody()->write($query_string['hub_challenge']);
        } else {
            $response->getBody()->write('Failed validation. Make sure the validation tokens match.');
            $response = $response->withStatus(403);
        }

        return $response;
    }

    public function main(Request $request, Response $response, array $args): Response
    {
        /** @var array<array<string, string>> $messages */
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
        $messages = [];

        if (!empty($input)) {
            $this->container->get('monolog')->debug($input);

            /** @var array<string, array<mixed>> $data */
            $data = json_decode($input, true);

            /** @var array<string, array<mixed>> $entry */
            foreach ($data['entry'] as $entry) {
                /** @var array<string, array<string, string>> $message */
                foreach ($entry['messaging'] as $message) {
                    if (array_key_exists('message', $message)) {
                        $datas = [];
                        $datas['sender_id'] = $message['sender']['id'];
                        $messages[] = $datas;
                    }
                }
            }
        }

        $this->container->get('monolog')->debug(json_encode($messages));

        return $messages;
    }

    public function process(array $messages): void
    {
        foreach ($messages as $message) {
            $this->sendRandomAnswer($message['sender_id']);
        }
    }

    public function sendRandomAnswer(string $sender): void
    {
        $answer = (mt_rand(0, 1) > 0.5) ? 'Yes.' : 'No.';

        $this->sendAPIRequestJson('messages', [
            'recipient' => [
                'id' => $sender
            ],
            'message' => [
                'text' => $answer
            ]
        ]);
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

        $this->container->get('monolog')->debug(json_encode($parameters));

        try {
            $response = $this->container->get('curl')->post(
                sprintf(
                    '%s/me/%s?access_token=%s',
                    $this->container->get('config')->get('network.facebook.api_endpoint'),
                    $method,
                    $this->container->get('config')->get('network.facebook.page_token'),
                ),
                ['json' => $parameters]
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
}
