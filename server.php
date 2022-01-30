<?php

// php server.php

use App\Adapter\ViewiReactAdapter;
use App\Controller\AuthSessionAction;
use App\Controller\AuthTokenAction;
use App\Controller\PostsAction;
use App\Controller\PostsActionAsync;
use App\Middleware\RequestsHandlerMiddleware;
use App\Middleware\StaticFilesMiddleware;
use Viewi\Routing\Route;

require __DIR__ . '/vendor/autoload.php';

$viewiRequestHandler = new RequestsHandlerMiddleware();
Route::setAdapter(new ViewiReactAdapter($viewiRequestHandler));

Viewi\Routing\Router::register('get', '/api/posts/{id}', new PostsAction());
Viewi\Routing\Router::register('get', '/api/posts/{id}/async/{ms?}', new PostsActionAsync());
Viewi\Routing\Router::register('post', '/api/authorization/session', new AuthSessionAction());
Viewi\Routing\Router::register('post', '/api/authorization/token/{valid}', new AuthTokenAction());

// include viewi routes
include __DIR__ . '/viewi-app/viewi.php';

$http = new React\Http\HttpServer(
    new StaticFilesMiddleware(__DIR__ . '/public'),
    $viewiRequestHandler
);

$socket = new React\Socket\SocketServer(isset($argv[1]) ? $argv[1] : '127.0.0.1:8080');
$http->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
