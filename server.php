<?php

// php server.php

use App\Bridge\ViewiReactBridge;
use App\Controller\AuthSessionAction;
use App\Controller\AuthTokenAction;
use App\Controller\PostsAction;
use App\Controller\PostsActionAsync;
use App\Middleware\RequestsHandlerMiddleware;
use App\Middleware\StaticFilesMiddleware;
use Viewi\Bridge\IViewiBridge;

require __DIR__ . '/vendor/autoload.php';

/**
 * @var \Viewi\App
 */
$viewiApp = include __DIR__ . '/viewi-app/viewi.php';
$router = $viewiApp->router();
$viewiRequestHandler = new RequestsHandlerMiddleware($router, $viewiApp);

$viewiReactBridge = new ViewiReactBridge($viewiRequestHandler);
$app->factory()->add(IViewiBridge::class, function () use ($viewiReactBridge) {
    return $viewiReactBridge;
});


$router->register('get', '/api/posts/{id}', new PostsAction());
$router->register('get', '/api/posts/{id}/async/{ms?}', new PostsActionAsync());
$router->register('post', '/api/authorization/session', new AuthSessionAction());
$router->register('post', '/api/authorization/token/{valid}', new AuthTokenAction());

// include viewi routes
include __DIR__ . '/viewi-app/routes.php';

$http = new React\Http\HttpServer(
    new StaticFilesMiddleware(__DIR__ . '/public'),
    $viewiRequestHandler
);

$socket = new React\Socket\SocketServer(isset($argv[1]) ? $argv[1] : '127.0.0.1:8080');
$http->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
