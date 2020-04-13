<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Zipkin\DefaultTracing;
use Zipkin\Endpoint;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Reporters\Http;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Span;
use Zipkin\TracingBuilder;

class Tracer
{
    const SERVICE_NAME = 'test-service';
    const ENDPOINT_URL = 'http://localhost:9411/api/v2/spans';

    public static function create(): \Zipkin\Tracer
    {
        return self::getTracing()->getTracer();
    }

    private static function getTracing(): DefaultTracing
    {
        $endpoint = Endpoint::create(self::SERVICE_NAME);
        $reporter = new Http(null, ['endpoint_url' => self::ENDPOINT_URL]);
        $sampler = BinarySampler::createAsAlwaysSample();

        return TracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();
    }

    public static function getPrevContextIfAny(): SamplingFlags
    {
        $reqHeaders = self::parseRequestHeaders();
        $extractor = self::getTracing()->getPropagation()->getExtractor(new Map());

        return $extractor($reqHeaders);
    }

    public static function getHeaderFrom(Span $span) {
        $headers = [];
        $injector = self::getTracing()->getPropagation()->getInjector(new Map());
        $injector($span->getContext(), $headers);

        return $headers;
    }

    private static function parseRequestHeaders(): array
    {
        $request = Request::createFromGlobals();

        return array_map(function ($header) {
            return $header[0];
        }, $request->headers->all());
    }
}