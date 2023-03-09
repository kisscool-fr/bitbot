<?php

namespace Bitbot\Network;

use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

use Bitbot\NetworkInterface;

class Slack implements NetworkInterface
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
        $input = file_get_contents('php://input');

        $this->container->get('monolog')->debug(file_get_contents('php://input'));

        if (!empty($input)) {
            $payload = json_decode($input, true);

            if (
                is_array($payload) &&
                $payload['type'] == 'url_verification' &&
                $payload['token'] == $this->container->get('config')->get('network.slack.verification_token')
            ) {
                $response->getBody()->write($payload['challenge']);
                return $response;
            }
        }

        $response->getBody()->write('Failed validation. Make sure the validation tokens match.');
        $response = $response->withStatus(403);
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, string> $args
     * @return Response
     */
    public function test(Request $request, Response $response, array $args): Response
    {
        $query_string = $request->getQueryParams();
        if (array_key_exists('code', $query_string)) {
            $parameters = [
                'client_id' => $this->container->get('config')->get('network.slack.client.id'),
                'client_secret' => $this->container->get('config')->get('network.slack.client.secret'),
                'code' => $query_string['code'],
                'redirect_uri' => $this->container->get('config')->get('network.slack.redirect_uri'),
            ];

            $this->container->get('monolog')->debug(json_encode($parameters));

            try {
                $response = $this->container->get('curl')->post(
                    sprintf('https://slack.com/api/oauth.access'),
                    json_encode($parameters)
                );
                $this->container->get('monolog')->debug($response->getBody());
            } catch (ClientException $e) {
                $exception = $e->getResponse();

                $this->container->get('monolog')->error(
                    sprintf(
                        "%d:%s",
                        $exception->getStatusCode(),
                        $exception->getReasonPhrase(),
                    )
                );
                $this->container->get('monolog')->debug(Psr7\Message::toString($exception));
            }
        }

        $response->getBody()->write(
            '<a href="https://slack.com/oauth/authorize?scope=bot&client_id='.$this->container->get('config')->get('network.slack.client.id').'"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>'
        );
        return $response;
    }

    public function main(Request $request, Response $response, array $args): Response
    {
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
                '',
                json_encode($parameters)
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
}
