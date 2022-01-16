<?php

namespace App\Adapter;

use App\Message\RawJsonResponse;
use React\Http\Message\ServerRequest;
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

    public function handle($method, $url, $params = null)
    {
        $request = new ServerRequest($method, $url);
        $response = ($this->requestHandler)($request);
        /** @var Psr7Response $response */
        if ($response instanceof RawJsonResponse) {
            return $response->getData();
        }
        return json_decode($response->getBody());
    }
}
