<?php

namespace App\Middleware;

use App\Http\ReactHttpContext;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Promise\Promise;
use Throwable;
use Viewi\App;
use Viewi\BaseComponent;
use Viewi\DI\Container;
use Viewi\Routing\Router;
use Viewi\WebComponents\IHttpContext;

class RequestsHandlerMiddleware
{
    public function __invoke(ServerRequestInterface $request)
    {
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
            if ($match['params']) {
                $request = $request->withAttribute('params', $match['params']);
            }
            return $action($request);
        }
        // Viewi component
        return new Promise(function ($resolve, $reject) use ($request, $action, $match) {
            try {
                /** @var BaseComponent $instance */
                // $instance = new $action();
                $container = new Container();
                $httpContext = new ReactHttpContext($request);
                $container->set(IHttpContext::class, $httpContext);
                $directOutput = App::getEngine()->render($action, $match['params'], $container, function ($viewiResponse) use ($httpContext, $resolve) {
                    // echo 'Viewi callback' . PHP_EOL;
                    // callback here
                    // $viewiResponse = $instance($match['params'], $container);
                    $headers = $httpContext->getResponseHeaders();
                    if (isset($headers['Location'])) {
                        $resolve(
                            new Response(
                                302,
                                $headers
                            )
                        );
                        return;
                    }
                    // $viewiResponse = Router::handle($request->getUri()->getPath(), $request->getMethod(), $request->getQueryParams());
                    // print_r(['response', $viewiResponse]);
                    // print_r(['context', $request->getUri()->getPath(), $request->getMethod(), $httpContext->getResponseHeaders()]);
                    if ($viewiResponse instanceof \Viewi\WebComponents\Response) {
                        $resolve(
                            new Response(
                                $viewiResponse->StatusCode,
                                array(
                                    'Content-Type' => 'text/html'
                                ) + $headers,
                                $viewiResponse->Content
                            )
                        );
                        return;
                    } else if (is_string($viewiResponse)) {
                        $resolve(
                            new Response(
                                200,
                                array(
                                    'Content-Type' => 'text/html'
                                ) + $headers,
                                $viewiResponse
                            )
                        );
                        return;
                    } else { // json
                        $resolve(new Response(
                            200,
                            array(
                                'Content-Type' => 'application/json'
                            ) + $headers,
                            json_encode($viewiResponse)
                        ));
                        return;
                    }
                });
            } catch (Throwable $t) {
                echo $t->getMessage() . "\n";
                // echo $t->getTraceAsString();
                // print_r($t);
                $resolve(new Response(
                    500,
                    array(
                        'Content-Type' => 'text/text'
                    ),
                    $t->getMessage() . $t->getTraceAsString()
                ));
            }
        });
    }
}
