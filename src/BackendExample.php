<?php

namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BackendExample
{
    private $logger;

    public function __construct()
    {
        $this->logger = Logger::create('backend');
    }

    public function run()
    {
        $this->logger->info('request received');

        $this->callDatabase();

        return JsonResponse::create(
            ['foo' => 'bar'],
            Response::HTTP_OK,
            Tracer::getInstance()->b3Headers()
        );
    }

    private function callDatabase()
    {
        Tracer::getInstance()->childSpan();

        $this->logger->info('querying database');

        $db = new \PDO('sqlite::memory:');
        $db->exec('CREATE TABLE IF NOT EXISTS public.zipkin(id INTEGER PRIMARY KEY)');
        $db->query('SELECT 1');
    }
}
