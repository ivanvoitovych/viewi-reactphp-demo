# ReactPHP ft. Viewi 2

This application demonstrates Viewi integration with ReactPHP.

## Requirements

`php >= 8.0`

`react/http`

`viewi/viewi`

`react/async`

`Node.js >= 12` (for building assets)

## Architecture design

ReactPHP will serve our API. 

And Viewi will be responsible for rendering HTML pages on the client-side as a front-end application. But also it will be responsible for rendering HTML on the server-side (SSR). In both cases, Viewi application can consume the server's API using HttpClient:

- In the browser - AJAX
- During SSR - simulate a request and pass it to the ReactPHP application (direct invocation)

(ReactPHP - API)   <-- data -->  (Viewi application)

## Demo Overview

Click here: [Demo Overview](DemoOverview.md)

## Integration steps

 - Install ReactPHP HTTP `composer require react/http`
 - Install ReactPHP Async `composer require react/async`
 - Install Viewi `composer require viewi/viewi`
 - Create `server.php` file for server code
 - Create a `public` folder for serving static and public files
 - Create a demo application if you don't have any `vendor/bin/viewi new`

## Configuration

Couple of important settings to consider here.

To run Viewi application you need to tell Viewi where to put its compiled files.
It should be a public folder.

In this case, it is a `public` folder:

 `__DIR__ . '/../public/'` 

Public url path to resolve assets, in this case it's an empty string `''` (means assets base URL is `http://localhost/`)

NPM watch setting, you can either use it, or you can disable it.

`->watchWithNPM(true)` or `->watchWithNPM(false)`

Setting it to `false` will trigger a build process on the first request.

The final config should look something like this:

`viewi-app\config.php`

```php
<?php

use Viewi\AppConfig;

$d = DIRECTORY_SEPARATOR;
$viewiAppPath = __DIR__ . $d;
$componentsPath =  $viewiAppPath . 'Components';
$buildPath = $viewiAppPath . 'build';
$jsPath = $viewiAppPath . 'js';
$assetsSourcePath = $viewiAppPath . 'assets';
$publicPath = __DIR__ . $d . '..' . $d . 'public';
$assetsPublicUrl = '';

return (new AppConfig('react'))
    ->buildTo($buildPath)
    ->buildFrom($componentsPath)
    ->withJsEntry($jsPath)
    ->putAssetsTo($publicPath)
    ->assetsPublicUrl($assetsPublicUrl)
    ->withAssets($assetsSourcePath)
    ->combine(false)
    ->minify(false)
    ->developmentMode(true)
    ->buildJsSourceCode()
    ->watchWithNPM(true);
```

## Implementation

Remove your `index.php` which contains Viewi standalone application code, you won't need it.

### Serve static files from a public folder

If you don't have a middleware for static files or your application is not behind any of the web servers (Apache, Nginx, etc.)
you will need `StaticFilesMiddleware` (Not production-ready, only for demo purposes).

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

The most important of the application is to handle requests. In this case, we need to handle two parts:

- Requests that should be handled by Viewi
- The rest of the requests should be handled by API actions

Viewi has a built-in router and is used in this example.
But it's not required, you can use any router that you like and use it with Viewi as well.

`viewi-app\viewi.php` contains your Viewi application instance initialization. Using that instance you can get router.

```php
use Viewi\App;

$config = require  __DIR__ . '/config.php';
$publicConfig = require  __DIR__ . '/publicConfig.php';

$app = new App($config, $publicConfig);

$router = $app->router();
```

```php
public function __invoke(ServerRequestInterface $request)
{
    $match = $this->router->resolve($request->getUri()->getPath(), $request->getMethod());
```

It will contain the following information:

- `$match['item']`: instance of `Viewi\Routing\RouteItem`
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

If not, that means we have Viewi component and we need to call render method:

```php
use Viewi\Components\Http\Message\Request;
// ...
if ($action instanceof ComponentRoute) {
    $viewiRequest = new Request($request->getUri()->getPath(), strtolower($request->getMethod()));
    $response = $this->viewiApp->engine()->render($action->component, $match['params'], $viewiRequest);
}
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

### Viewi React bridge

By default, Viewi uses its own internal request/response handler. To tell Viewi that we need to handle request/response with ReactPHP we need to set up a bridge:

```php
interface IViewiBridge
{
    // file_exists - Checks whether a file or directory exists
    function file_exists(string $filename): bool;
    
    // is_dir - Tells whether the filename is a directory
    function is_dir(string $filename): bool;
    
    // file_get_contents - Reads entire file into a string
    function file_get_contents(string $filename): string | false;
    
    // request - Server-side internal request handler. Request that comes from Viewi component.
    function request(Request $request): mixed;
}
```

Default bridge: `Viewi\Bridge\DefaultBridge`.

We can reuse some of the methods and override only a request handler.

For the `request` method we will need `RequestsHandlerMiddleware` to process internal requests. Let's inject it in the constructor:

```php
class ViewiReactBridge extends DefaultBridge
{
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
public function request(\Viewi\Components\Http\Message\Request $request): mixed
{
    $reactRequest = new \React\Http\Message\ServerRequest(
            $request->method,
            $request->url,
            $request->headers,
            $request->body ? json_encode($request->body) : ''
        );
    $response = ($this->requestHandler)($reactRequest);
    if ($response instanceof PromiseInterface) {
        $response = await($response);
    }
```

Also, the url could be external and we need to make a real HTTP call, le's use `\React\Http\Browser`:

```php
public function request(\Viewi\Components\Http\Message\Request $request): mixed
{
    if ($request->isExternal) {
        $browser = new \React\Http\Browser();
        $promise = $browser->request($request->method, $request->url, $request->headers, $request->body ? json_encode($request->body) : '');
        $response = await($promise);
        return @json_decode($response->getBody(), true);
    }
// ...
```

At the end, the `request` method should return either a `\Viewi\Components\Http\Message\Response` instance, or just any raw data (array, model class instance, anything that is serializable into JSON).

To set up a new bridge for Viewi application we will use Viewi factory:

```php
$viewiReactBridge = new ViewiReactBridge($viewiRequestHandler);
// \Viewi\App
$app->factory()->add(IViewiBridge::class, function () use ($viewiReactBridge) {
    return $viewiReactBridge;
});
```

Full code:

`App\Bridge\ViewiReactBridge.php`

```php
<?php

namespace App\Bridge;

use App\Message\RawJsonResponse;
use React\Http\Message\ServerRequest;
use React\Promise\PromiseInterface;
use Viewi\Bridge\DefaultBridge;
use Viewi\Components\Http\Message\Request;

use function React\Async\await;

class ViewiReactBridge extends DefaultBridge
{
    /**
     * 
     * @var callable
     */
    private $requestHandler;

    public function __construct($requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    public function request(Request $request): mixed
    {
        if ($request->isExternal) {
            $browser = new \React\Http\Browser();
            $promise = $browser->request($request->method, $request->url, $request->headers, $request->body ? json_encode($request->body) : '');
            $response = await($promise);
            return @json_decode($response->getBody(), true);
        }

        $reactRequest = new ServerRequest($request->method, $request->url, $request->headers, $request->body ? json_encode($request->body) : '');
        $response = ($this->requestHandler)($reactRequest);
        if ($response instanceof PromiseInterface) {
            $response = await($response);
        }

        /**
         * @var \React\Http\Message\Response $response
         */

        $viewiResponse = new \Viewi\Components\Http\Message\Response($request->url, $response->getStatusCode(), $response->getReasonPhrase(), $response->getHeaders());
        if ($response instanceof RawJsonResponse) {
            $viewiResponse->body = $response->getData();
        } else {
            $data = $response->getBody()->__toString();
            if ($data) {
                $viewiResponse->body = @json_decode($data, true);
            }
        }

        return $viewiResponse;
    }
}
```

### Using `react/async`

In order to convert asynchronous response from ReactPHP action and pass it to Viewi we need to extract it from promise as a `\React\Http\Message\Response` instance. To do that we will use `await` function:

```php
use React\Promise\PromiseInterface;
use function React\Async\await;
// ...
if ($response instanceof PromiseInterface) {
    $response = await($response);
}
```

### Extracting data from HTTP call for components

In ReactPHP, response by default implements `Psr\Http\Message\ResponseInterface`, therefore the content is always a string (html or json).

But if you want to use typed function arguments inside of callbacks from `HttpClient` requests (see example), it's recommended to use `RawJsonResponse` declared in `App\Message\RawJsonResponse.php`

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
```

### Build Viewi application

Viewi application is not about PHP. It is also a fully capable JavaScript application.

To set it up you need to perform simple steps.

If you are using `vendor/bin/viewi new` that may not be necessary.

But if you are cloning this repository you will need to install NPM packages.

Assuming that you have installed `composer` packages:

`cd viewi-app/js`

`npm install`

Wait for the installation.

### Watching mode

Watching mode will monitor your Viewi application for changes and will trigger a build process automatically.

Go to your Viewi application `js` folder

`cd viewi-app/js`

Run NPM watch command

`npm run watch`

You will need to keep two terminals open in order to run this and ReactPHP server for development.

Watch mode is optional, please follow [https://viewi.net/docs/watch-mode](https://viewi.net/docs/watch-mode) for more.

### Step by step 

- Instantiate `\Viewi\App` and keep int a variable
    - `$viewiApp = include __DIR__ . '/viewi-app/viewi.php';`
- Get Viewi router if using
    - `$router = $viewiApp->router();`
- Instantiate `RequestsHandlerMiddleware` and keep it in a variable
    - `$viewiRequestHandler = new RequestsHandlerMiddleware($router, $viewiApp);`
- Create a bridge for Viewi: 
    - `$viewiReactBridge = new ViewiReactBridge($viewiRequestHandler);`

```php
$app->factory()->add(IViewiBridge::class, function () use ($viewiReactBridge) {
    return $viewiReactBridge;
});
```

- Register your actions:
    - For example: `$router->register('get', '/api/posts/{id}', new PostsAction());`
- Include Viewi routes (for components)
    - `include __DIR__ . '/viewi-app/routes.php';`
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
