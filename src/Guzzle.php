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
            /**
             * @var $reqHeader
             * {
             *     "x-b3-traceid": "d4ca90093540675a",
             *     "x-b3-spanid": "a453a149ba41debc",
             *     "x-b3-parentspanid": "d4ca90093540675a",
             *     "x-b3-sampled": "1",
             *     "x-b3-flags": "0"
             * }
             */
            $reqHeader = Tracer::getInstance()->b3Headers();

            foreach ($reqHeader as $k => $v) {
                /**
                 * NOTE 3. Current span will be propagated to the backend through b3-* request headers
                 */
                $req = $req->withHeader($k, $v);
            }

            return $req;
        }));
        $stack->push(Middleware::mapResponse(function (ResponseInterface $res) {
            /**
             * NOTE 6. The span from backend will be relayed through b3-* response headers
             */
            Tracer::getInstance()->nextSpan($res->getHeaders());

            return $res;
        }));

        return new Client(['handler' => $stack]);
    }
}