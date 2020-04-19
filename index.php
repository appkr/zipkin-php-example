<?php
/**
 * Front controller
 * Referenced from @see https://symfony.com/doc/current/create_framework/front_controller.html
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require __DIR__.'/vendor/autoload.php';

$request = Request::createFromGlobals();
$response = new Response();

$map = [
    '/frontend' => App\FrontendExample::class,
    '/backend' => App\BackendExample::class,
];

$path = $request->getPathInfo();
if (isset($map[$path])) {
    try {
        $object = new $map[$path]();
        $response = $object->run();
    } catch (Throwable $e) {
        $response->setStatusCode(404);
        $response->setContent('Not Found');
    }
} else {
    $response->setStatusCode(404);
    $response->setContent('Not Found');
}

$response->send();
