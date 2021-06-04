<?php

namespace Bitbot\Network;

use Bitbot\NetworkInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Slack implements NetworkInterface
{
    private $app;

    public function verifyToken(Request $request, Application $app): Response
    {
        $input = file_get_contents('php://input');

        $app['monolog']->addDebug(file_get_contents('php://input'));

        if (!empty($input)) {
            $payload = json_decode($input, true);

            if (is_array($payload) && $payload['type'] == 'url_verification' && $payload['token'] == $app['network']['slack']['verification_token']) {
                return new Response($payload['challenge']);
            }
        }

        return new Response('Failed validation. Make sure the validation tokens match.', 403);
    }

    public function test(Request $request, Application $app): Response
    {
        if ($request->get('code')) {
            $parameters = [
                'client_id' => $app['network']['slack']['client']['id'],
                'client_secret' => $app['network']['slack']['client']['secret'],
                'code' => $request->get('code'),
                'redirect_uri' => $app['network']['slack']['redirect_uri'],
            ];

            $app['monolog']->addDebug(json_encode($parameters));

            $app['curl']->setopt(CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
            $app['curl']->setopt(CURLOPT_RETURNTRANSFER, true);
            $app['curl']->setopt(CURLOPT_CONNECTTIMEOUT, 5);
            $app['curl']->setopt(CURLOPT_TIMEOUT, 60);
            $app['curl']->post(
                sprintf('https://slack.com/api/oauth.access'),
                json_encode($parameters)
            );

            if ($app['curl']->error) {
                $app['monolog']->addError($app['curl']->error_code.':'.$app['curl']->response);
            }

            $app['monolog']->addDebug(json_encode($app['curl']->response));
        }

        return new Response('<a href="https://slack.com/oauth/authorize?scope=bot&client_id='.$app['network']['slack']['client']['id'].'"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>', 200);
    }

    public function main(Request $request, Application $app): Response
    {
        $this->app = $app;

        $messages = $this->decode();

        $this->app['monolog']->addDebug('count messages:'.count($messages));

        if (count($messages) == 0) {
            return new Response('', 200);
        }

        $this->process($messages);

        return new Response('', 200);
    }

    public function decode(): array
    {
        $input = file_get_contents('php://input');
        $messages = [];

        if (!empty($input)) {
            $this->app['monolog']->addDebug($input);
            $data = json_decode($input, true);

            foreach ($data['entry'] as $entry) {
                foreach ($entry['messaging'] as $message) {
                    if (array_key_exists('message', $message)) {
                        $datas = [];
                        $datas['sender_id'] = $message['sender']['id'];
                        $messages[] = $datas;
                    }
                }
            }
        }

        $this->app['monolog']->addDebug(json_encode($messages));

        return $messages;
    }

    public function process(array $messages): void
    {
        foreach ($messages as $message) {
            $this->sendRandomAnswer($message['sender_id']);
        }
    }

    public function sendAPIRequestJson(string $method, array $parameters): string
    {
        if (!is_string($method)) {
            return false;
        }

        if (!$parameters) {
            $parameters = array();
        } elseif (!is_array($parameters)) {
            return false;
        }

        $this->app['monolog']->addDebug(json_encode($parameters));

        $this->app['curl']->setopt(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $this->app['curl']->setopt(CURLOPT_RETURNTRANSFER, true);
        $this->app['curl']->setopt(CURLOPT_CONNECTTIMEOUT, 5);
        $this->app['curl']->setopt(CURLOPT_TIMEOUT, 60);
        $this->app['curl']->post(
            '',
            json_encode($parameters)
        );

        if ($this->app['curl']->error) {
            $this->app['monolog']->addError($this->app['curl']->error_code.':'.$this->app['curl']->response);
        }
        
        return $this->app['curl']->response;
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
