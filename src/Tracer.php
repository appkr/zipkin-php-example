<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Zipkin\DefaultTracing;
use Zipkin\Propagation\B3;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\SamplingFlags;
use Zipkin\Span;
use Zipkin\TracingBuilder;

class Tracer
{
    const SERVICE_NAME = 'test-service';
    const ENDPOINT_URL = 'http://localhost:9411/api/v2/spans';

    /** @var Tracer */
    private static $INSTANCE;

    /** @var DefaultTracing */
    private static $tracing;

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
     * Start new span, succeed the previous context if it exists
     *
     * @return Tracer
     */
    public function currentSpan(): Tracer
    {
        if ($this->currentSpan == null) {
            $this->initZipkin();

            $this->currentSpan = self::$tracing->getTracer()->nextSpan(
                $this->getPreviousContextIfAny()
            );

            $self = $this;
            register_shutdown_function(function () use ($self) {
                $self->currentSpan->finish();
                self::$tracing->getTracer()->flush();
            });

            $this->currentSpan->start();
        }

        return $this;
    }

    /**
     * Start new span, succeed a context from downstream response
     *
     * @param array $resHeaders {
     *     @var string $key e.g. content-type
     *     @var array $values {
     *         @var string $value e.g. application/json
     *     }
     * }
     * @return $this
     */
    public function nextSpan(array $resHeaders = []): Tracer
    {
        if ($this->currentSpan == null) {
            $this->currentSpan();
        }

        $carrier = $this->rearrangeHeaders($resHeaders);

        $extractor = self::$tracing->getPropagation()->getExtractor(new Map());

        $this->currentSpan = self::$tracing->getTracer()->nextSpan($extractor($carrier));

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

        $this->currentSpan = self::$tracing->getTracer()->newChild($this->currentSpan->getContext());

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
        private function initZipkin(string $serviceName = null, string $endpointUrl = null): void
        {
            if (self::$tracing == null) {
                self::$tracing = TracingBuilder::create()->build();

                // We do not report to zipkin, instead we only use b3-* trace
                // $endpoint = \Zipkin\Endpoint::create($serviceName ?: static::SERVICE_NAME);
                // $reporter = new \Zipkin\Reporters\Http(null, ['endpoint_url' => $endpointUrl ?: static::ENDPOINT_URL]);
                // $sampler = \Zipkin\Samplers\BinarySampler::createAsAlwaysSample();

                // TracingBuilder::create()
                    // ->havingSampler($sampler)
                    // ->havingLocalEndpoint($endpoint)
                    // ->havingReporter($reporter)
                    // ->build()
            }
        }

        /**
         * Parse and get previous context from http request, only if it exists
         *
         * @return SamplingFlags
         * - TraceContext if trace and span IDs were present
         * - SamplingFlags if no identifiers were present
         */
        private function getPreviousContextIfAny(): SamplingFlags
        {
            $request = Request::createFromGlobals();

            $carrier = $this->rearrangeHeaders($request->headers->all());

            $extractor = self::$tracing->getPropagation()->getExtractor(new Map());

            return $extractor($carrier);
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
        private function rearrangeHeaders(array $headers): array
        {
            return array_map(function ($header) {
                return $header[0];
            }, $headers);
        }

    /**
     * Extract and get x-b3-* header from the current span
     *
     * @return array {
     *      @var string x-b3-traceid 128 or 64 lower-hex encoded bits (required)
     *      @var string x-b3-spanid 64 lower-hex encoded bits (required)
     *      @var string x-b3-parentspanid 64 lower-hex encoded bits (absent on root span)
     *      @var string x-b3-sampled Boolean (either “1” or “0”, can be absent)
     *      @var string x-b3-flags “1” means debug (can be absent)
     * }
     * for details @see https://github.com/openzipkin/b3-propagation
     */
    public function b3Headers(): array
    {
        if ($this->currentSpan == null) {
            $this->currentSpan();
        }

        $b3Headers = [];
        $injector = (new B3())->getInjector(new Map());
        $injector($this->currentSpan->getContext(), $b3Headers);

        return $b3Headers;
    }
}
