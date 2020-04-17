<?php

namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BackendExample
{
    private $logger;

    public function __construct()
    {
        /**
         * NOTE 4. A new singleton instance of Tracer created,
         *     but thanks to the b3-* request header, the Tracer will relay the previous span
         */
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
        )->send();
    }

    private function callDatabase()
    {
        /**
         * NOTE 5. Start a child span in backend
         */
        Tracer::getInstance()->childSpan();

        $this->logger->info('querying database');

        $db = new \PDO('sqlite::memory:');
        $db->exec('CREATE TABLE IF NOT EXISTS zipkin(id INTEGER PRIMARY KEY)');
        $db->query('SELECT 1 FROM zipkin');
    }
}
