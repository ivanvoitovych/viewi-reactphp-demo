<?php

// php server.php

use RingCentral\Psr7\Response as Psr7Response;
use App\Middleware\StaticFilesMiddleware;
use Components\Models\PostModel;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use Viewi\Routing\Route;
use Viewi\Routing\RouteAdapterBase;
use Viewi\Routing\RouteItem;
use Viewi\Routing\Router;

require __DIR__ . '/vendor/autoload.php';

class RawJsonResponse extends Psr7Response
{
    private $data = null;
    /**
     * @param int                                            $status  HTTP status code (e.g. 200/404)
     * @param array<string,string|string[]>                  $headers additional response headers
     * @param string|ReadableStreamInterface|StreamInterface $body    response body
     * @param string                                         $version HTTP protocol version (e.g. 1.1/1.0)
     * @param ?string                                        $reason  custom HTTP response phrase
     * @throws \InvalidArgumentException for an invalid body
     */
    public function __construct(
        $data,
        $status = 200,
        array $headers = array(),
        $version = '1.1',
        $reason = null
    ) {
        $this->data = $data;
        $body = json_encode($data) . "\n";
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        parent::__construct(
            $status,
            $headers,
            $body,
            $version,
            $reason
        );
    }

    public function getData()
    {
        return $this->data;
    }
}

$viewiRequestHandler = function (ServerRequestInterface $request) {
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
            return $action($request);
        }
        $instance = new $action();
        $viewiResponse = $instance($match['params']);
        // $viewiResponse = Router::handle($request->getUri()->getPath(), $request->getMethod(), $request->getQueryParams());
        // print_r(['response', $viewiResponse]);
        if ($viewiResponse instanceof Viewi\WebComponents\Response) {
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
};

class ViewiReactAdapter extends RouteAdapterBase
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

    public function register($method, $url, $component, $defaults)
    {
        // nothing if you use Viewi router
    }

    public function handle($method, $url, $params = null)
    {
        $response = ($this->requestHandler)(new ServerRequest($method, $url));
        /** @var Psr7Response $response */
        if ($response instanceof RawJsonResponse) {
            return $response->getData();
        }
        return json_decode($response->getBody());
    }
}

Route::setAdapter(new ViewiReactAdapter($viewiRequestHandler));

$apiDataHandler = function (ServerRequestInterface $request) {
    $post = new PostModel();
    $post->Name = 'Viewi ft. ReactPHP';
    $post->Version = 1;
    return new RawJsonResponse($post);
};

Viewi\Routing\Router::register('get', '/api/data', function (ServerRequestInterface $request) {
    $post = new PostModel();
    $post->Name = 'Viewi ft. ReactPHP';
    $post->Version = 1;
    return new RawJsonResponse($post);
});

// include viewi routes
include __DIR__ . '/viewi-app/viewi.php';

$http = new React\Http\HttpServer(
    StaticFilesMiddleware::get(__DIR__),
    $viewiRequestHandler
);

$socket = new React\Socket\SocketServer(isset($argv[1]) ? $argv[1] : '127.0.0.1:8080');
$http->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
