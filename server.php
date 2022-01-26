<?php

// php server.php

use App\Adapter\ViewiReactAdapter;
use App\Message\RawJsonResponse;
use App\Middleware\RequestsHandlerMiddleware;
use App\Middleware\StaticFilesMiddleware;
use Components\Models\PostModel;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\Message\Response;
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

Viewi\Routing\Router::register('get', '/api/posts/{id}/async/{ms?}', function (ServerRequestInterface $request) {
    return new Promise(function ($resolve, $reject) use ($request) {
        $ms = $request->getAttribute('params')['ms'] ?? 1000;
        if ($ms > 5000) // we don't want it to be more than 5 sec
        {
            $ms = 5000;
        }
        Loop::addTimer($ms / 1000 + (rand(1, 20) / 100), function () use ($resolve, $request) {
            $postId = $request->getAttribute('params')['id'] ?? 0;
            $post = new PostModel();
            $post->Id = $postId;
            $post->Name = "Viewi ft. ReactPHP $postId";
            $post->Version = $postId + 1000;
            $response = new RawJsonResponse($post);
            // echo "request Loop:1. \n";
            $resolve($response);
        });
    });
});

Viewi\Routing\Router::register('post', '/api/authorization/session', function (ServerRequestInterface $request) {
    return new Promise(function ($resolve, $reject) use ($request) {
        Loop::addTimer(0.2, function () use ($resolve, $request) {
            $response = new RawJsonResponse(['session' => '000-1111-2222']);
            $resolve($response);
        });
    });
});

Viewi\Routing\Router::register('post', '/api/authorization/token/{valid}', function (ServerRequestInterface $request) {
    return new Promise(function ($resolve, $reject) use ($request) {
        Loop::addTimer(0.2, function () use ($resolve, $request) {
            $valid = $request->getAttribute('params')['valid'] === 'true';
            if (!$valid) {
                $resolve(new Response(401, [], ''));
                return;
            }
            $response = new RawJsonResponse(['token' => 'base64string']);
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
