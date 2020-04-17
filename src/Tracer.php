<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Zipkin\Endpoint;
use Zipkin\Propagation\B3;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Reporters\Http;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Span;
use Zipkin\Tracer as ZipkinTracer;
use Zipkin\TracingBuilder;

class Tracer
{
    const SERVICE_NAME = 'test-service';
    const ENDPOINT_URL = 'http://localhost:9411/api/v2/spans';

    /** @var Tracer */
    private static $INSTANCE;

    /** @var ZipkinTracer */
    private $zipkin  = null;

    /** @var Span */
    private $currentSpan = null;

    /**
     * Get a singleton instance
     *
     * @return Tracer
     */
    public static function getInstance(): Tracer
    {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new Tracer();
        }

        return self::$INSTANCE;
    }

    private function __construct()
    {
    }

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
     * @param array $resHeaders {
     *     @var string $key e.g. content-type
     *     @var array $values {
     *         @var string $value e.g. application/json
     *     }
     * }
     * @return $this
     */
    public function nextSpan(array $resHeaders)
    {
        if ($this->currentSpan == null) {
            $this->currentSpan();
        }

        $parsedHeaders = $this->parseHeaders($resHeaders);
        $extractor = (new B3())->getExtractor(new Map());

        $this->currentSpan = $this->zipkin->nextSpan($extractor($parsedHeaders));

        $self = $this;
        register_shutdown_function(function () use ($self) {
            $self->currentSpan->finish();
        });

        $this->currentSpan->start();

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

        $this->currentSpan = $this->zipkin->newChild($this->currentSpan->getContext());

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
                    // We do not report to zipkin, instead we only use b3-* trace
                    // ->havingReporter($reporter)
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
            $request = Request::createFromGlobals();

            $reqHeaders = $this->parseHeaders($request->headers->all());
            if (empty($reqHeaders)) {
                return $reqHeaders;
            }

            $extractor = (new B3())->getExtractor(new Map());

            return $extractor($reqHeaders);
        }

        /**
         * Parse and get request headers
         *
         * @param array $headers {
         *     @var string $key e.g. content-type
         *     @var array $values {
         *         @var string $value e.g. application/json
         *     }
         * }
         * @return array {
         *     @var string $key
         *     @var string $value
         * }
         */
        private function parseHeaders(array $headers): array
        {
            return array_map(function ($header) {
                return $header[0];
            }, $headers);
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
        if ($this->currentSpan == null) {
            $this->currentSpan();
        }

        $context = [];
        $injector = (new B3())->getInjector(new Map());
        $injector($this->currentSpan->getContext(), $context);

        return $context;
    }
}
