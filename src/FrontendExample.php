<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FrontendExample
{
    const BACKEND_ENDPOINT = 'http://localhost:8001/backend.php';

    private $logger;

    public function __construct()
    {
        $this->logger = Logger::create('frontend');
    }

    public function run()
    {
        /*
         * IMPORTANT NOTE. Tracer is an MUTABLE object.
         */
        $tracer = new Tracer();
        $current = $tracer->currentSpan();
        $ctx1 = $current->b3Headers();
        $this->logger->info('Current Span', $ctx1);

        $child = $tracer->childSpan();
        $ctx2 = $child->b3Headers();
        $this->logger->info('Child Span', $ctx2);
        $response = $this->callBackend($ctx2);

        $body = json_decode($response->getBody()->getContents(), true);
        $headers = array_map(function ($h) { return $h[0]; }, $response->getHeaders());
        $this->logger->info('Response from Backend', [
            'headers' => $headers,
            'body' => $body,
        ]);

        return (new JsonResponse($body, $response->getStatusCode(), $headers))->send();
    }

    private function callBackend(array $reqHeader): ResponseInterface
    {
        $httpClient = new Client();
        $request = new Request('GET', self::BACKEND_ENDPOINT, $reqHeader);

        return $httpClient->send($request);
    }
}