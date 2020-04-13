<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Span;
use Zipkin\Tracer as ZipkinTracer;

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
        $tracer = Tracer::create();
        $prevContext = Tracer::getPrevContextIfAny();
        $curSpan = $tracer->nextSpan(Tracer::getPrevContextIfAny());
        if ($prevContext instanceof DefaultSamplingFlags) {
            $curSpan = $tracer->newTrace();
        }

        $childSpan = $tracer->newChild($curSpan->getContext());
        $this->registerShutdownFunction($tracer, $childSpan, $curSpan);

        $resHeader = Tracer::getHeaderFrom($curSpan);
        $reqHeader = Tracer::getHeaderFrom($childSpan);
        $response = $this->callBackend($reqHeader);

        $this->logger->info('Current Context', $resHeader);
        $this->logger->info('Child Context', $reqHeader);

        $body = json_decode($response->getBody()->getContents(), true);
        $this->logger->info('Response from Backend', [
            'header' => array_map(function ($h) { return $h[0]; }, $response->getHeaders()),
            'body' => $body,
        ]);

        return (new JsonResponse($body, $response->getStatusCode(), $resHeader))->send();
    }

    private function registerShutdownFunction(ZipkinTracer $tracer, Span ...$spans)
    {
        register_shutdown_function(function () use ($spans, $tracer) {
            foreach ($spans as $span) {
                $span->finish();
            }
            $tracer->flush();
        });
    }

    private function callBackend(array $reqHeader): ResponseInterface
    {
        $httpClient = new Client();
        $request = new Request('GET', self::BACKEND_ENDPOINT, $reqHeader);

        return $httpClient->send($request);
    }
}