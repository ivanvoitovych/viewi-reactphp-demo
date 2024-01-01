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
