<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Guzzle
{
    public static function create(): Client
    {
        /**
         * For Guzzle Middleware @see http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#middleware
         */
        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(function (RequestInterface $req) {
            $reqHeader = Tracer::getInstance()->b3Headers();

            foreach ($reqHeader as $k => $v) {
                $req = $req->withHeader($k, $v);
            }

            return $req;
        }));
        $stack->push(Middleware::mapResponse(function (ResponseInterface $res) {
            Tracer::getInstance()->nextSpan($res->getHeaders());

            return $res;
        }));

        return new Client(['handler' => $stack]);
    }
}