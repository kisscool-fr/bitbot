<?php

namespace Bitbot;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface NetworkInterface
{
    public function main(Request $request, Application $app): Response;
    /**
     * @return array<array>
     */
    public function decode(): array;
    /**
     * @param array<array> $messages
     */
    public function process(array $messages): void;

    /**
     * @param array<string, string> $parameters
     */
    public function sendAPIRequestJson(string $method, array $parameters): string;
}
