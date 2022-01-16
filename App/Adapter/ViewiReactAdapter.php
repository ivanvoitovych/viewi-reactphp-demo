<?php

namespace App\Adapter;

use App\Message\RawJsonResponse;
use React\Http\Message\ServerRequest;
use React\Promise\Promise;
use Viewi\Common\PromiseResolver;
use Viewi\Routing\RouteAdapterBase;

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
        /** @var Psr7Response $response */
        if ($response instanceof RawJsonResponse) {
            return $response->getData();
        }
        return json_decode($response->getBody());
    }

    public function handle($method, $url, $params = null)
    {
        $request = new ServerRequest($method, $url);
        $response = ($this->requestHandler)($request);

        if ($response instanceof Promise) {
            // handle Promise
            // ??? await or Fiber ??? php 8.1 only
            // Async SSR - work in progress
            // Async Viewi render status: IN PROGRESS, once done - no await required here
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
