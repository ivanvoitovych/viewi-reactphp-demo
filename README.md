# ReactPHP ft. Viewi

This application demonstrates Viewi integration with ReactPHP.

## Requirements

`php >= 7.4`

`react/http`

`viewi/viewi`

## Steps to integrate your Viewi app with ReactPHP

```php
// get request handler middleware (with Viewi Router)
// put into a variable, will use it couple of times
$viewiRequestHandler = new RequestsHandlerMiddleware();
Route::setAdapter(new ViewiReactAdapter($viewiRequestHandler));

// register your API routes and handlers
Viewi\Routing\Router::register('get', '/api/posts/{id}', function (ServerRequestInterface $request) {
    $post = new PostModel();
    $post->Id = $request->getAttribute('params')['id'] ?? 0;
    $post->Name = 'Viewi ft. ReactPHP';
    $post->Version = 1;
    return new RawJsonResponse($post);
});

// include viewi routes (!Include only after API routes as it has 404 handler which catches everything!)
include __DIR__ . '/viewi-app/viewi.php';

// include request handler middleware into your server
$http = new React\Http\HttpServer(
    new StaticFilesMiddleware(__DIR__),
    $viewiRequestHandler
);

// and run the server
$socket = new React\Socket\SocketServer(isset($argv[1]) ? $argv[1] : '127.0.0.1:8080');
$http->listen($socket);
```


## TODO:

- Deferred (async) data during SSR.
- Headers pass through (cookies, authorization. etc.) during internal requests.
- Post/Explanation.
- Package library.
- Documentation.
- Router helper ($router->get(...), etc).
- Router params injection into the action: 'get', '/api/posts/{id}', function (ServerRequestInterface $request, int $id)

### Thanks and feel free to review, ask questions, contribute in any way.

## Links:
___

[Viewi Twitter](https://twitter.com/viewiphp)

[ReactPHP Twitter](https://twitter.com/reactphp)

[Viewi Website](https://viewi.net/)

[ReactPHP Website](https://reactphp.org/)

[Viewi Github](https://github.com/viewi/viewi)

[ReactPHP Github](https://github.com/reactphp)

License
--------

MIT License

Copyright (c) 2020-present Ivan Voitovych

Please see [LICENSE](/LICENSE) for license text