<?php

namespace App;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FrontendExample
{
    const BACKEND_ENDPOINT = 'http://localhost:8001/backend';

    private $logger;

    public function __construct()
    {
        /**
         * NOTE. A singleton instance of Tracer will be created when we instantiate a Logger
         * @see \App\Logger::create
         */
        $this->logger = Logger::create('frontend');
    }

    public function run()
    {
        /**
         * NOTE. An extra log context will be logged, whenever we call a method in the Logger
         * e.g. [2020-04-17 15:17:45] frontend.INFO: request received [] {"traceId":"d4ca90093540675a","spanId":"b1425953c9a965b0","parentSpanId":"d4ca90093540675a"}
         */
        $this->logger->info('request received');

        $response = $this->callBackend();

        $this->logger->info('response received from backend');

        return JsonResponse::fromJsonString(
            $response->getBody()->getContents(),
            $response->getStatusCode(),
            Tracer::getInstance()->b3Headers()
        );
    }

    private function callBackend(): ResponseInterface
    {
        Tracer::getInstance()->childSpan();

        $this->logger->info('calling backend');

        $httpClient = Guzzle::create();
        $request = new Request('GET', self::BACKEND_ENDPOINT);

        return $httpClient->send($request);
    }
}
