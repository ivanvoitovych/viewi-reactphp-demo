<?php

namespace App\Adapter;

use App\Message\RawJsonResponse;
use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use React\Promise\Promise;
use Viewi\Common\PromiseResolver;
use Viewi\Routing\RouteAdapterBase;
use Viewi\WebComponents\Response as WebComponentsResponse;

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

    public function handle($method, $url, $params = null)
    {
        $request = new ServerRequest($method, $url);
        $response = ($this->requestHandler)($request);

        if ($response instanceof Promise) {
            // handle Promise
            return new PromiseResolver(function (callable $resolve, callable $reject) use ($response) {
                $response->then(function ($innerResponse) use ($resolve) {
                    $data = $this->handleInternal($innerResponse);
                    $resolve($data);
                }, $reject);
            });
        }
        return $this->handleInternal($response);
    }
}
