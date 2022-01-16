<?php

// php server.php

use App\Adapter\ViewiReactAdapter;
use App\Message\RawJsonResponse;
use App\Middleware\RequestsHandlerMiddleware;
use App\Middleware\StaticFilesMiddleware;
use Components\Models\PostModel;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Promise\Promise;
use Viewi\Routing\Route;

require __DIR__ . '/vendor/autoload.php';


$viewiRequestHandler = new RequestsHandlerMiddleware();
Route::setAdapter(new ViewiReactAdapter($viewiRequestHandler));

Viewi\Routing\Router::register('get', '/api/posts/{id}', function (ServerRequestInterface $request) {
    $post = new PostModel();
    $post->Id = $request->getAttribute('params')['id'] ?? 0;
    $post->Name = 'Viewi ft. ReactPHP';
    $post->Version = 1;
    return new RawJsonResponse($post);
});

Viewi\Routing\Router::register('get', '/api/posts/{id}/async', function (ServerRequestInterface $request) {
    return new Promise(function ($resolve, $reject) use ($request) {
        Loop::addTimer(0.5, function () use ($resolve, $request) {
            $post = new PostModel();
            $post->Id = $request->getAttribute('params')['id'] ?? 0;
            $post->Name = 'Viewi ft. ReactPHP';
            $post->Version = 1;
            $response = new RawJsonResponse($post);
            $resolve($response);
        });
    });
});

// include viewi routes
include __DIR__ . '/viewi-app/viewi.php';

$http = new React\Http\HttpServer(
    new StaticFilesMiddleware(__DIR__),
    $viewiRequestHandler
);

$socket = new React\Socket\SocketServer(isset($argv[1]) ? $argv[1] : '127.0.0.1:8080');
$http->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
