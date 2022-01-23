<?php

use Components\Views\AsyncTest\AsyncTestComponent;
use Components\Views\Home\HomePage;
use Components\Views\InterceptorsTest\InterceptorsTestComponent;
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
ViewiRoute::get('*', NotFoundPage::class);
