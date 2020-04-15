<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Zipkin\Endpoint;
use Zipkin\Propagation\B3;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Propagation\TraceContext;
use Zipkin\Reporters\Http;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Span;
use Zipkin\Tracer as ZipkinTracer;
use Zipkin\TracingBuilder;

class Tracer
{
    const SERVICE_NAME = 'test-service';
    const ENDPOINT_URL = 'http://localhost:9411/api/v2/spans';

    /** @var ZipkinTracer */
    private $zipkin  = null;

    /** @var Span */
    private $currentSpan = null;

    /**
     * Start a new span, succeed the previous context if it exists
     *
     * @return Tracer
     */
    public function currentSpan(): Tracer
    {
        if ($this->currentSpan == null) {
            $this->initZipkin();
            $this->currentSpan = $this->zipkin->nextSpan(
                $this->getPreviousContext()
            );

            $self = $this;
            register_shutdown_function(function () use ($self) {
                $self->currentSpan->finish();
                $self->zipkin->flush();
            });

            $this->currentSpan->start();
        }

        return $this;
    }

    /**
     * Start a child span
     *
     * @return $this
     */
    public function childSpan(): Tracer
    {
        if ($this->currentSpan == null) {
            $this->currentSpan();
        }

        $this->currentSpan = $this->zipkin->newChild($this->context());

        $self = $this;
        register_shutdown_function(function () use ($self) {
            $self->currentSpan->finish();
        });

        $this->currentSpan->start();

        return $this;
    }

        /**
         * Initialize new instance of Zipkin\Tracer
         *
         * @param string|null $serviceName Name of the service you are running, default to test-service
         * @param string|null $endpointUrl Zipkin api endpoint, default to http://localhost:9411/api/v2/spans
         */
        private function initZipkin(string $serviceName = null, string $endpointUrl = null)
        {
            if ($this->zipkin == null) {
                $endpoint = Endpoint::create($serviceName ?: static::SERVICE_NAME);
                $reporter = new Http(null, ['endpoint_url' => $endpointUrl ?: static::ENDPOINT_URL]);
                $sampler = BinarySampler::createAsAlwaysSample();

                $this->zipkin = TracingBuilder::create()
                    ->havingLocalEndpoint($endpoint)
                    ->havingSampler($sampler)
                    ->havingReporter($reporter)
                    ->build()
                    ->getTracer();
            }
        }

        /**
         * Parse and get previous context from http request, only if it exists
         *
         * @return SamplingFlags
         */
        private function getPreviousContext(): SamplingFlags
        {
            $reqHeaders = static::parseRequestHeaders();
            if (empty($reqHeaders)) {
                return $reqHeaders;
            }

            $extractor = (new B3())->getExtractor(new Map());

            return $extractor($reqHeaders);
        }

        /**
         * Parse and get request headers
         *
         * @return array
         */
        private static function parseRequestHeaders(): array
        {
            $request = Request::createFromGlobals();

            return array_map(function ($header) {
                return $header[0];
            }, $request->headers->all());
        }

        private function getZipkin(): ZipkinTracer
        {
            return $this->zipkin;
        }

    /**
     * Get trace context of the current span
     *
     * @return TraceContext
     */
    public function context(): TraceContext
    {
        return $this->currentSpan->getContext();
    }

    /**
     * Extract and get x-b3-* header from the current span
     *
     * @return array {
     *      @var string x-b3-traceid 64bit or 128bit hex
     *      @var string x-b3-spanid 64bit or 128bit hex
     *      @var string x-b3-parentspanid 64bit or 128bit hex
     *      @var string x-b3-sampled 1 or 0
     *      @var string x-b3-flags 1 or 0
     * } for details @see https://github.com/openzipkin/b3-propagation
     */
    public function b3Headers(): array
    {
        $context = [];
        $injector = (new B3())->getInjector(new Map());
        $injector($this->context(), $context);

        return $context;
    }
}
