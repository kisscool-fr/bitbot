<?php

namespace Bitbot\Network;

use Bitbot\NetworkInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Facebook implements NetworkInterface
{
    const FB_API_ENDPOINT = 'https://graph.facebook.com/v2.7';

    private $app;

    public function verifyToken(Request $request)
    {
        if ($request->get('hub_verify_token') == FB_APP_TOKEN) {
            return $request->get('hub_challenge');
        }

        return new Response('Failed validation. Make sure the validation tokens match.', 403);
    }

    public function main(Request $request, Application $app)
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

    public function decode()
    {
        $input = file_get_contents('php://input');
        $messages = [];

        if (!empty($input))
        {
            $this->app['monolog']->addDebug($input);
            $data = json_decode($input, true);

            foreach ($data['entry'] as $entry)
            {
                foreach ($entry['messaging'] as $message)
                {
                    if (array_key_exists('message', $message))
                    {
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

    public function process($messages)
    {
        foreach ($messages as $message)
        {
            $this->sendRandomAnswer($message['sender_id']);
        }
    }

    public function sendAPIRequestJson($method, $parameters)
    {
        if (!is_string($method)) {
            return false;
        }

        if (!$parameters) {
            $parameters = array();
        } else if (!is_array($parameters)) {
            return false;
        }

        $this->app['monolog']->addDebug(json_encode($parameters));

        $this->app['curl']->setopt(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $this->app['curl']->setopt(CURLOPT_RETURNTRANSFER, true);
        $this->app['curl']->setopt(CURLOPT_CONNECTTIMEOUT, 5);
        $this->app['curl']->setopt(CURLOPT_TIMEOUT, 60);
        $this->app['curl']->post(
            sprintf('%s/me/%s?access_token=%s', self::FB_API_ENDPOINT, $method, FB_PAGE_TOKEN),
            json_encode($parameters)
        );

        if ($this->app['curl']->error) {
            $this->app['monolog']->addError($this->app['curl']->error_code.':'.$this->app['curl']->response);
        }
        
        return $this->app['curl']->response;
    }

    public function sendRandomAnswer($sender)
    {
        $answer = (mt_rand(0,1) > 0.5) ? 'Yes.' : 'No.';

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
