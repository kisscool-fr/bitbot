<?php

namespace Bitbot\Network;

use Bitbot\NetworkInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Telegram implements NetworkInterface
{
    const API_URL   = 'https://api.telegram.org/bot'.TELEGRAM_TOKEN.'/';

    private $app;

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

        $this->app['monolog']->addDebug($input);

        $data = json_decode($input, true);

        $messages = [];

        if (is_array($data) && array_key_exists('message', $data))
        {
            $message = $data['message'];

            $datas = [];
            $datas['chat_id'] = $message['chat']['id'];

            $messages[] = $datas;
        }

        $this->app['monolog']->addDebug(json_encode($messages));

        return $messages;
    }

    public function process($messages)
    {
        foreach ($messages as $message)
        {
            $this->sendRandomAnswer($message['chat_id']);
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

        $parameters['method'] = $method;

        $this->app['curl']->setopt(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $this->app['curl']->setopt(CURLOPT_RETURNTRANSFER, true);
        $this->app['curl']->setopt(CURLOPT_CONNECTTIMEOUT, 5);
        $this->app['curl']->setopt(CURLOPT_TIMEOUT, 60);
        $this->app['curl']->post(self::API_URL, json_encode($parameters));

        if ($this->app['curl']->error) {
            $this->app['monolog']->addError($this->app['curl']->error_code.':'.$this->app['curl']->response);
        }
        
        return $this->app['curl']->response;
    }

    public function sendRandomAnswer($chat_id)
    {
        $answer = (mt_rand(0,1) > 0.5) ? 'Yes.' : 'No.';

        $this->sendAPIRequestJson('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $answer,
            'parse_mode' => 'Markdown'
        ]);
    }
}
