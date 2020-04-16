<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
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
        $this->logger->info('Current Span', $current->b3Headers());

        $response = $this->callBackend($tracer);

        $body = json_decode($response->getBody()->getContents(), true);
        $headers = array_map(function ($h) { return $h[0]; }, $response->getHeaders());
        $this->logger->info('Response from Backend', [
            'headers' => $headers,
            'body' => $body,
        ]);

        return (new JsonResponse($body, $response->getStatusCode(), $headers))->send();
    }

    private function callBackend(Tracer $tracer): ResponseInterface
    {
        $child = $tracer->childSpan();

        /**
         * @var $reqHeader
         * {
         *     "x-b3-traceid": "d4ca90093540675a",
         *     "x-b3-spanid": "a453a149ba41debc",
         *     "x-b3-parentspanid": "d4ca90093540675a",
         *     "x-b3-sampled": "1",
         *     "x-b3-flags": "0"
         * }
         */
        $reqHeader = $child->b3Headers();
        $this->logger->info('Child Span', $reqHeader);

        /**
         * For Guzzle Middleware @see http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#middleware
         */
        $stack = HandlerStack::create();
        $stack->push(function (callable $handler) use ($reqHeader) {
            return function (RequestInterface $req, array $options) use ($handler, $reqHeader){
                foreach ($reqHeader as $k => $v) {
                    $req = $req->withHeader($k, $v);
                }
                return $handler($req, $options);
            };
        });

        $httpClient = new Client(['handler' => $stack]);
        $request = new Request('GET', self::BACKEND_ENDPOINT);

        return $httpClient->send($request);
    }
}