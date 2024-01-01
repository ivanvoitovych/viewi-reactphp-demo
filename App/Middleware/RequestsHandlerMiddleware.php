<?php

namespace App\Middleware;

use App\Message\RawJsonResponse;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use Throwable;
use Viewi\App;
use Viewi\Components\Http\Message\Request;
use Viewi\Components\Http\Message\Response as ViewiResponse;
use Viewi\Router\ComponentRoute;
use Viewi\Router\Router;
use Viewi\Routing\RouteItem;

class RequestsHandlerMiddleware
{
    public function __construct(private Router $router, private App $viewiApp)
    {
    }

    public function __invoke(ServerRequestInterface $request)
    {
        try {
            $match = $this->router->resolve($request->getUri()->getPath(), $request->getMethod());
            if ($match === null) {
                throw new Exception('No route was matched!');
            }
            /** @var RouteItem */
            $routeItem = $match['item'];
            $action = $routeItem->action;
            $response = '';
            if (is_callable($action) && !is_string($action)) {
                if ($match['params']) {
                    $request = $request->withAttribute('params', $match['params']);
                }
                return $action($request);
            } elseif ($action instanceof ComponentRoute) {
                $viewiRequest = new Request($request->getUri()->getPath(), strtolower($request->getMethod()));
                $response = $this->viewiApp->engine()->render($action->component, $match['params'], $viewiRequest);
            } else {
                throw new Exception('Unknown action type.');
            }

            if (is_string($response)) { // string as html
                return new Response(
                    200,
                    array(
                        'Content-Type' => 'text/html; charset=utf-8'
                    ),
                    $response
                );
            } elseif ($response instanceof ViewiResponse) {
                return new Response(
                    isset($response->headers['Location']) ? 302 : $response->status,
                    $response->headers,
                    is_string($response->body) ? $response->body : json_encode($response->body)
                );
            } elseif ($response instanceof RawJsonResponse) {
                return $response;
            } else { // json
                new Response(
                    200,
                    array(
                        'Content-Type' => 'application/json'
                    ),
                    json_encode($response)
                );
            }
        } catch (Throwable $t) {
            echo $t->getMessage() . "\n";
            // echo $t->getTraceAsString();
            // !! For production: Consider using $reject and don't output stack trace
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
