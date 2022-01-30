# ReactPHP ft. Viewi

This application demonstrates Viewi integration with ReactPHP.

## Requirements

`php >= 7.4`

`react/http`

`viewi/viewi`

## Architecture design

ReactPHP will server our API. 

And Viewi will be responsible for rendering html pages on client side as a front-end application.
But also it will be responsible for rendering html on server side (SSR).
In both cases, Viewi application can consume server's API using HttpClient:

- In the browser - AJAX
- During SSR - simulate a request and pass it to the ReactPHP application (direct invocation)

(ReactPHP - API)   <-- data -->  (Viewi application)

## Demo Overview

Click here: [Demo Overview](DemoOverview.md)

## Integration steps

 - Install ReactPHP Http `composer require react/http`
 - Create `server.php` file for server code
 - Create `public` folder for serving static and public files
 - Install Viewi `composer require viewi/viewi`
 - Create a demo application if you don't have any `vendor/bin/viewi new -e`

## Configuration

To run Viewi application you need to tell Viewi where to put its compiled files.
It should be public folder.

In this case it is `public` folder:

 `PageEngine::PUBLIC_ROOT_DIR => __DIR__ . '/../public/'` 

The final config should look something like this:

`viewi-app\config.php`

```php
<?php

use Viewi\PageEngine;

return [
    PageEngine::SOURCE_DIR =>  __DIR__ . '/Components',
    PageEngine::SERVER_BUILD_DIR =>  __DIR__ . '/build',
    PageEngine::PUBLIC_ROOT_DIR => __DIR__ . '/../public/',
    PageEngine::DEV_MODE => true,
    PageEngine::RETURN_OUTPUT => true,
    PageEngine::COMBINE_JS => true
];
```

## Implementation

Remove your `index.php` which contains Viewi standalone application code, you won't need it.

### Serve static files from public folder

If you don't have a middleware for static files or you application is not behind any of the web servers (Apache, Nginx, etc.)
you will need `StaticFilesMiddleware` (Not production ready, only for demo purposes).

`App\Middleware\StaticFilesMiddleware.php`

```php
<?php

namespace App\Middleware;

use React\Http\Message\Response;

class StaticFilesMiddleware
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request, callable $next)
    {
        $filePath = $request->getUri()->getPath();
        $file = $this->directory . $filePath;
        if (file_exists($file) && !is_dir($file)) {
            $fileExt = pathinfo($file, PATHINFO_EXTENSION);
            $contentType = 'text/text';
            switch ($fileExt) {
                case 'js': {
                        $contentType = 'application/javascript';
                        break;
                    }
                case 'json': {
                        $contentType = 'application/json';
                        break;
                    }
                case 'css': {
                        $contentType = 'text/css';
                        break;
                    }
                case 'ico': {
                        $contentType = 'image/x-icon';
                        break;
                    }
            }
            return new Response(200, ['Content-Type' => $contentType], file_get_contents($file));
        }
        return $next($request);
    }
}
```

And use it like this:

```php
new React\Http\HttpServer(
    new StaticFilesMiddleware(__DIR__ . '/public') // path to your public folder
...
```

### Request handler

Most import part of the application is to handle requests. In this case we need to handle two parts:

- Requests that should be handled by Viewi
- The rest of requests, that should be handled by API actions

Viewi has built-in router and it's used in this example.
But it's not required, you can use any router that you like and use it with Viewi as well.

To get the route simply use `Viewi\Routing\Router::resolve` method:

```php
public function __invoke(ServerRequestInterface $request)
{
    $match = Router::resolve($request->getUri()->getPath(), $request->getMethod());
```

It will contain the following information:

- `$match['route']`: instance of `Viewi\Routing\RouteItem`
    - action: string|callable - class name or callable
- `$match['params']`: array of matched arguments from the path (/api/posts/{id} -> ['id' => '5'])

Next part is to handle the action. If it's `callable` and not a `string` - just call the handler:

```php
/** @var RouteItem $route */
$route = $match['route'];
$action = $route->action;
if (is_callable($action) && !is_string($action)) {
    if ($match['params']) {
        $request = $request->withAttribute('params', $match['params']);
    }
    return $action($request);
}
```

If not, that means we have Viewi component and we need to call render in asynchronous mode:

- Wrap the call inside of a Promise and return it

```php
...
return new Promise(function ($resolve, $reject) use ($request, $action, $match) {
...
```

- Instantiate `Viewi\DI\Container` for isolated scope

```php
...
$container = new Container();
```

- Register an instance of `ReactHttpContext` that implements `Viewi\WebComponents\IHttpContext` interface.

```php
...
$httpContext = new ReactHttpContext($request);
$container->set(IHttpContext::class, $httpContext);
```

This will provide for Viewi some useful information about the current request.
And also will collect response headers in case Viewi component will make redirect or access is not allowed for current user, etc.

```php
<?php

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;
use Viewi\WebComponents\IHttpContext;

class ReactHttpContext implements IHttpContext
{
    private array $responseHeaders = [];
    private ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getResponseHeaders(): ?array
    {
        return $this->responseHeaders;
    }

    public function setResponseHeader(string $key, string $value): void
    {
        $this->responseHeaders[$key] = $value;
    }

    public function getCurrentUrl(): ?string
    {
        return $this->request->getUri()->getPath();
    }
}
```

- Run render in async mode

```php
App::getEngine()->render(
    $action,
    $match['params'],
    $container,
    function ($viewiResponse) use ($httpContext, $resolve) {
    // use $viewiResponse: string | \Viewi\WebComponents\Response
...
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
...
```

The full code is located here `App\Middleware\RequestsHandlerMiddleware.php`

Use it as a regular middleware:

```php
$viewiRequestHandler = new RequestsHandlerMiddleware();
...
$http = new React\Http\HttpServer(
    new StaticFilesMiddleware(__DIR__ . '/public'),
    $viewiRequestHandler
);
```

## ReactPHP adapter for Viewi

To make things work during SSR you need to tell Viewi how to invoke the request on server side by extending `Viewi\Routing\RouteAdapterBase`.
It has the following abstract methods:

- `register($method, $url, $component, $defaults);` - used when you have a custom routing system. In case you use Viewi router it's not needed.
- `handle($method, $url, $params = null);` - used when `HttpClient` calls an API during SSR.

In this example we don't need `register`, so keep it empty:

```php
public function register($method, $url, $component, $defaults)
{
    // nothing if you are using Viewi router
}
```

For `handle` method we will need `RequestsHandlerMiddleware` to process internal requests. Let's inject it in the constructor:

```php
/**
 * 
 * @var callable
 */
private $requestHandler;

public function __construct($requestHandler)
{
    $this->requestHandler = $requestHandler;
}
```

Next, we need to create an instance of `React\Http\Message\ServerRequest` and pass it to the `RequestsHandlerMiddleware`:

```php
public function handle($method, $url, $params = null)
{
    $request = new ServerRequest($method, $url);
    $response = ($this->requestHandler)($request);
```

The response could be an instance of `React\Promise\Promise` or it could be an instance of `Psr\Http\Message\ResponseInterface`.

If it's a promise - we need to return an instance of `Viewi\Common\PromiseResolver` to the Viewi 
which will wait for `React\Promise\Promise` to be resolved. Otherwise - just return the response from invocation:

```php
if ($response instanceof Promise) {
    // handle Promise
    return new PromiseResolver(function (callable $resolve, callable $reject) use ($response) {
        $response->then(function ($innerResponse) use ($resolve) {
            $data = $this->handleInternal($innerResponse); // 
            $resolve($data);
        }, $reject);
    });
}
return $this->handleInternal($response);
```

`handleInternal` method will take the response and convert it to `Viewi\WebComponents\Response` or a string (if response code is 200).

```php
private function handleInternal($response)
{
    if ($response instanceof RawJsonResponse) {
        return $response->getData();
    }
    /** @var Response $response */
    if ($response->getStatusCode() != 200) {
        return (new WebComponentsResponse())
            ->WithContent(json_decode($response->getBody()))
            ->WithCode($response->getStatusCode())
            ->WithHeaders($response->getHeaders());
    }
    return json_decode($response->getBody());
}
```

In ReactPHP, response by default implements `Psr\Http\Message\ResponseInterface`, therefore the content is always a string (html or json).
But if you want to use typed function arguments inside of callbacks from HttpClient requests (see example), it's recommended to use `RawJsonResponse` declared in `App\Message\RawJsonResponse.php`

```php
$http->get('/api/posts/45')->then(
    function (PostModel $post) {
        $this->post = $post;
    },
...
```

`RawJsonResponse` will preserve the original data without losing the type.

### server.php

The last step is to set up your server:

```php
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
```

- Instantiate `RequestsHandlerMiddleware` and keep it in a variable
    - `$viewiRequestHandler = new RequestsHandlerMiddleware();`
- set an adapter for Viewi: 
    - `Route::setAdapter(new ViewiReactAdapter($viewiRequestHandler));`
- Register your actions:
    - For example: `Viewi\Routing\Router::register('get', '/api/posts/{id}', new PostsAction());`
- Include Viewi routes (for components)
    - `include __DIR__ . '/viewi-app/viewi.php';`
- Create a http server
    - `$http = new React\Http\HttpServer(`
    - pass `StaticFilesMiddleware` if needed
    - pass $viewiRequestHandler middleware
- Create a socket server
    - `$socket = new React\Socket\SocketServer(isset($argv[1]) ? $argv[1] : '127.0.0.1:8080');`
- Run the server by listening to the socket events:
    - `$http->listen($socket);`

And you can run it: `php server.php`

## Things to improve:

- [ ] Package library.
- [ ] Router helper ($router->get(...), etc).
- [ ] Router params injection into the action: 'get', '/api/posts/{id}', function (ServerRequestInterface $request, int $id)

### Thanks and feel free to review, ask questions, contribute in any way.

## Links:

[Discussions](https://github.com/viewi/viewi/discussions)

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
