<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use Throwable;
use Viewi\Routing\Router;

class RequestsHandlerMiddleware
{
    public function __invoke(ServerRequestInterface $request) {
        try {
            // print_r([$request->getUri()->getPath(), $request->getMethod(), $request->getQueryParams()]);
            $match = Router::resolve($request->getUri()->getPath(), $request->getMethod());
            if (!$match) {
                return new Response(
                    200,
                    array(
                        'Content-Type' => 'text/plain'
                    ),
                    "Page not Found!\n"
                );
            }
    
            /** @var RouteItem $route */
            $route = $match['route'];
            $action = $route->action;            
            if (is_callable($action) && !is_string($action)) {
                // print_r([$request->getUri()->getPath(), $request->getMethod(), $action, $match['params']]);
                if($match['params'])
                {
                    $request = $request->withAttribute('params', $match['params']);
                }
                return $action($request);
            }            
            $instance = new $action();
            $viewiResponse = $instance($match['params']);
            // $viewiResponse = Router::handle($request->getUri()->getPath(), $request->getMethod(), $request->getQueryParams());
            // print_r(['response', $viewiResponse]);
            if ($viewiResponse instanceof \Viewi\WebComponents\Response) {
                return new Response(
                    $viewiResponse->StatusCode,
                    array(
                        'Content-Type' => 'text/html'
                    ),
                    $viewiResponse->Content
                );
            } else if (is_string($viewiResponse)) {
                return new Response(
                    200,
                    array(
                        'Content-Type' => 'text/html'
                    ),
                    $viewiResponse
                );
            } else { // json
                return new Response(
                    200,
                    array(
                        'Content-Type' => 'application/json'
                    ),
                    json_encode($viewiResponse)
                );
            }
        } catch (Throwable $t) {
            echo $t->getMessage() . "\n";
            // echo $t->getTraceAsString();
            // print_r($t);
            return new Response(
                500,
                array(
                    'Content-Type' => 'text/text'
                ),
                $t->getMessage() . $t->getTraceAsString()
            );
        }
    }
}
