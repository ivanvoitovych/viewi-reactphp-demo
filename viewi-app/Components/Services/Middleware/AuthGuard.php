<?php

namespace Components\Services\Middleware;

use Viewi\Components\Http\HttpClient;
use Viewi\Components\Middleware\IMIddleware;
use Viewi\Components\Middleware\IMIddlewareContext;
use Viewi\Components\Routing\ClientRoute;
use Viewi\DI\Singleton;

#[Singleton]
class AuthGuard implements IMiddleware
{
    public function __construct(private HttpClient $http, private ClientRoute $router)
    {
    }

    public function run(IMIddlewareContext $c)
    {
        $this->http->post('/api/authorization/token/true')->then(function ($response) use ($c) {
            // all good - call $c->next(); or $c->next(true);
            $c->next();
        }, function () use ($c) {
            // If we want to cancel - we call $c->next(false);
            $c->next(false);
            $this->router->navigate('/');
        });
    }
}
