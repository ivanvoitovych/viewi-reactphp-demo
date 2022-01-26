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
use Viewi\Routing\Route as ViewiRoute;

ViewiRoute::get('/', HomePage::class);
ViewiRoute::get('/counter', CounterPage::class);
ViewiRoute::get('/counter/{page}', CounterPage::class);
ViewiRoute::get('/todo', TodoAppPage::class);
ViewiRoute::get('/redirect-test', RedirectTestComponent::class);
ViewiRoute::get('/current-url', CurrentUrlTestPage::class); 
ViewiRoute::get('/async-ssr-test/{id}', AsyncTestComponent::class);
ViewiRoute::get('/interceptors-test/{id}', InterceptorsTestComponent::class);
ViewiRoute::get('/middleware-test/{id}', MiddlewareTestComponent::class);
ViewiRoute::get('/middleware-fail-test/{id}', MiddlewareFailTestComponent::class);
ViewiRoute::get('*', NotFoundPage::class);
