<?php

namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BackendExample
{
    private $logger;

    public function __construct()
    {
        $this->logger = Logger::create('backend');
    }

    public function run()
    {
        $tracer = new Tracer();
        $current = $tracer->currentSpan();
        $this->logger->info("Current Span", $current->b3Headers());

        return $this->createResponse(['foo' => 'bar'], $current->b3Headers())->send();
    }

    private function createResponse(array $content, array $headers): Response
    {
        return JsonResponse::create($content, Response::HTTP_OK, $headers);
    }
}
