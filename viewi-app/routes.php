<?php

use Components\Views\AsyncTest\AsyncTestComponent;
use Components\Views\Home\HomePage;
use Components\Views\InterceptorsTest\InterceptorsTestComponent;
use Components\Views\MiddlewareTest\MiddlewareFailTestComponent;
use Components\Views\MiddlewareTest\MiddlewareTestComponent;
use Components\Views\NotFound\NotFoundPage;
use Components\Views\Pages\CounterPage;
use Components\Views\Pages\CurrentUrlTestPage;
use Components\Views\Pages\RedirectTestComponent;
use Components\Views\Pages\TodoAppPage;
use Viewi\App;
use Viewi\Components\Http\Message\Response;

/**
 * @var App $app
 */
$router = $app->router();

$router->get('/', HomePage::class);
$router->get('/counter', CounterPage::class);
$router->get('/counter/{page}', CounterPage::class);
$router->get('/todo', TodoAppPage::class);
$router->get('/redirect-test', RedirectTestComponent::class);
$router->get('/current-url', CurrentUrlTestPage::class);
$router->get('/async-ssr-test/{id}', AsyncTestComponent::class);
$router->get('/interceptors-test/{id}', InterceptorsTestComponent::class);
$router->get('/middleware-test/{id}', MiddlewareTestComponent::class);
$router->get('/middleware-fail-test/{id}', MiddlewareFailTestComponent::class);
$router
    ->get('*', NotFoundPage::class)
    ->transform(function (Response $response) {
        return $response->withStatus(404, 'Not Found');
    });
