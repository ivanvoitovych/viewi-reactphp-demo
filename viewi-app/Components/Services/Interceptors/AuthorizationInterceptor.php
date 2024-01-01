<?php

namespace Components\Services\Interceptors;

use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Interceptor\IHttpInterceptor;
use Viewi\Components\Http\Interceptor\IRequestHandler;
use Viewi\Components\Http\Interceptor\IResponseHandler;
use Viewi\Components\Http\Message\Request;
use Viewi\Components\Http\Message\Response;
use Viewi\DI\Singleton;

#[Singleton]
class AuthorizationInterceptor implements IHttpInterceptor
{
    public function __construct(private HttpClient $http)
    {
    }

    public function request(Request $request, IRequestHandler $handler)
    {
        // set request headers $request->withHeader
        // call handle to continue with the request
        $this->http->post('/api/authorization/token/true')->then(function ($response) use ($request, $handler) {
            $newRequest = $request->withHeader('Authorization', $response['token']);
            $handler->next($newRequest);
        }, function ($error) use ($request, $handler) {
            $handler->reject($request);
        });
    }

    public function response(Response $response, IResponseHandler $handler)
    {
        // access or modify $response
        // call $handler->next if you are good with the response
        // call $handler->reject to reject the response
        if ($response->status === 0) {
            // rejected
            // make it Unauthorized
            $response->status = 401;
            $response->body = 'Unauthorized';
        }
        $handler->next($response);
    }
}
