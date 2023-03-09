<?php

namespace Bitbot;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

interface NetworkInterface
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, string> $args
     * @return Response
     */
    public function main(Request $request, Response $response, array $args): Response;

    /**
     * @return array<mixed>
     */
    public function decode(): array;

    /**
     * @param array<array<string, string>> $messages
     */
    public function process(array $messages): void;

    /**
     * @param string $method
     * @param array<string, array<string, string>> $parameters
     * @return string|bool
     */
    public function sendAPIRequestJson(string $method, array $parameters): string|bool;
}
