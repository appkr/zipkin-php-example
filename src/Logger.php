<?php

namespace App;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class Logger
{
    const LOG_PATH = 'logs/app.log';

    public static function create(): LoggerInterface
    {
        $logger = new \Monolog\Logger('log');
        $logger->pushHandler(new ErrorLogHandler());
        $logger->pushHandler(new StreamHandler(self::LOG_PATH));

        return $logger;
    }
}