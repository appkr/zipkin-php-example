<?php

namespace App;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FrontendExample
{
    const BACKEND_ENDPOINT = 'http://localhost:8001/backend.php';

    private $logger;

    public function __construct()
    {
        /**
         * NOTE 0. A singleton instance of Tracer will be created when we instantiate a Logger
         * @see \App\Logger::create
         */
        $this->logger = Logger::create('frontend');
    }

    public function run()
    {
        /**
         * NOTE 1. An extra log context will be logged, whenever we call a method in the Logger
         * e.g. [2020-04-17 15:17:45] frontend.INFO: request received [] {"traceId":"d4ca90093540675a","spanId":"b1425953c9a965b0","parentSpanId":"d4ca90093540675a"}
         */
        $this->logger->info('request received');

        $response = $this->callBackend();
        $body = json_decode($response->getBody()->getContents(), true);

        $this->logger->info('response received from backend');

        /**
         * NOTE 7. The b3-* response header will be attached to the response
         */
        return (new JsonResponse($body, $response->getStatusCode(), Tracer::getInstance()->b3Headers()))->send();
    }

    private function callBackend(): ResponseInterface
    {
        /**
         * NOTE 2. Start a child span
         */
        Tracer::getInstance()->childSpan();

        $this->logger->info('calling backend');

        $httpClient = Guzzle::create();
        $request = new Request('GET', self::BACKEND_ENDPOINT);

        return $httpClient->send($request);
    }
}
