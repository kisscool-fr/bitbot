<?php

namespace Bitbot;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

interface NetworkInterface
{
    public function main(Request $request, Application $app);
    public function decode();
    public function process($messages);

    public function sendAPIRequestJson($method, $parameters);
}