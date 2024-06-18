<?php

namespace App\Bridge;

use App\Message\RawJsonResponse;
use React\Http\Message\ServerRequest;
use React\Promise\PromiseInterface;
use Viewi\Bridge\DefaultBridge;
use Viewi\Components\Http\Message\Request;
use Viewi\Engine;

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

    public function request(Request $request, Engine $engine): mixed
    {
        if ($request->isExternal) {
            // HTTP call to some third party resource
            $browser = new \React\Http\Browser();
            $promise = $browser->request($request->method, $request->url, $request->headers, $request->body ? json_encode($request->body) : '');
            $response = await($promise);
            return @json_decode($response->getBody(), true);
        }

        $reactRequest = new ServerRequest($request->method, $request->url, $request->headers, $request->body ? json_encode($request->body) : '');
        $response = ($this->requestHandler)($reactRequest, true);
        if ($response instanceof PromiseInterface) {
            $response = await($response);
        }
        $data = null;
        if ($response instanceof RawJsonResponse) {
            $data = $response->getData();
            $response = $response->getResponse();
        } else {
            $data = @json_decode($response->getBody()->__toString(), true);
        }
        /**
         * @var \React\Http\Message\Response $response
         */
        $viewiResponse = new \Viewi\Components\Http\Message\Response($request->url, $response->getStatusCode(), $response->getReasonPhrase(), $response->getHeaders(), $data);
        return $viewiResponse;
    }
}
