<?php

namespace App;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class Logger
{
    const LOG_PATH = 'logs/app.log';

    public static function create(string $name = 'log'): LoggerInterface
    {
        $tracer = Tracer::getInstance();

        $logger = new \Monolog\Logger($name);
        $logger->pushHandler(new ErrorLogHandler());
        $logger->pushHandler(new StreamHandler(self::LOG_PATH));
        $logger->pushProcessor(function (array $record) use ($tracer) {
            $ctx = $tracer->b3Headers();
            $record['extra']['traceId'] = $ctx['x-b3-traceid'] ?? null;
            $record['extra']['spanId'] = $ctx['x-b3-spanid'] ?? null;
            $record['extra']['parentSpanId'] = $ctx['x-b3-parentspanid'] ?? null;
            return $record;
        });

        return $logger;
    }
}