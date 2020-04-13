<?php

namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Span;
use Zipkin\Tracer as ZipkinTracer;

class BackendExample
{
    private $logger;

    public function __construct()
    {
        $this->logger = Logger::create('backend');
    }

    public function run()
    {
        $tracer = Tracer::create();
        $prevContext = Tracer::getPrevContextIfAny();
        $curSpan = $tracer->nextSpan(Tracer::getPrevContextIfAny());
        if ($prevContext instanceof DefaultSamplingFlags) {
            $curSpan = $tracer->newTrace();
        }

        $this->registerShutdownFunction($tracer, $curSpan);

        $curSpan->start();
        $resHeaders = Tracer::getHeaderFrom($curSpan);
        $this->logger->info("Response Headers", $resHeaders);

        return $this->createResponse(['foo' => 'bar'], $resHeaders)->send();
    }

    private function registerShutdownFunction(ZipkinTracer $tracer, Span $span)
    {
        register_shutdown_function(function () use ($span, $tracer) {
            $span->finish();
            $tracer->flush();
        });
    }

    private function createResponse(array $content, array $headers): Response
    {
        return JsonResponse::create($content, Response::HTTP_OK, $headers);
    }
}
