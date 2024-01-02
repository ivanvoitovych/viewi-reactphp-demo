# ReactPHP and Viewi demo application overview

## Home page

[http://localhost:8080/](http://localhost:8080/)

Home page loads one post using HttpClient to call an API

![Alt text](screenshots/homePage.png?raw=true "Home Page")

```php
<?php

namespace Components\Views\Home;

use Components\Models\PostModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;

class HomePage extends BaseComponent
{
    public string $title = 'Viewi - Reactive application for PHP';
    public ?PostModel $post = null;

    public function __construct(private HttpClient $http)
    {
    }

    public function init()
    {
        $this->http->get('/api/posts/45')->then(
            function (PostModel $post) {
                $this->post = $post;
            },
            function ($error) {
                echo $error;
            }
        );
    }
}
```

```html
<Layout title="$title">
    <h1>$title</h1>
    <div><strong>Data from the Server:</strong> {json_encode($post)}</div>
    <div if="$post">
        <h2>{$post->Name}</h2>
        <div>
            <strong>Id: {$post->Id}</strong>
        </div>
        <div>
            <i>Version: {$post->Version}</i>
        </div>
    </div>
</Layout>
```

PostsAction middleware for handling requests to the API endpoint `/api/posts/{id}`

```php
class PostsAction
{
    public function __invoke(ServerRequestInterface $request) {
        $post = new PostModel();
        $post->Id = $request->getAttribute('params')['id'] ?? 0;
        $post->Name = 'Viewi ft. ReactPHP';
        $post->Version = 1;
        return new RawJsonResponse($post);
    }
}
```

## Async SSR

[http://localhost:8080/async-ssr-test/100500](http://localhost:8080/async-ssr-test/100500)

This page demonstrates async server side rendering. It loads 4 posts in async mode. 

`PostsActionAsync` simulates I/O DB read with random timer value.

![Alt text](screenshots/async.png?raw=true "Async SSR")

```html
<Layout title="$title">
    <h2>$title</h2>
    <p>This page loads 4 posts in non-blocking asynchronous mode.</p>
    <PostComponent id="1"></PostComponent>
    <PostComponent id="2"></PostComponent>
    <PostComponent id="3"></PostComponent>
    <PostComponent id="4"></PostComponent>
</Layout>
```

```php
<?php

namespace Components\Views\AsyncTest;

use Components\Models\PostModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;

class PostComponent extends BaseComponent
{
    public ?int $id = 0;
    public ?PostModel $post = null;

    public function __construct(private HttpClient $http)
    {
    }

    public function mounted()
    {
        $this->http->get("/api/posts/{$this->id}/async")->then(
            function (PostModel $post) {
                $this->post = $post;
                // print_r(['$http->get->then->success', $post]);
            },
            function ($error) {
                echo $error;
                // print_r(['$http->get->then->error', $error]);
            }
        );
    }
}
```

`PostsActionAsync`

```php
<?php

namespace App\Controller;

use App\Message\RawJsonResponse;
use Components\Models\PostModel;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Promise\Promise;

class PostsActionAsync
{
    public function __invoke(ServerRequestInterface $request)
    {
        return new Promise(function ($resolve, $reject) use ($request) {
            $ms = $request->getAttribute('params')['ms'] ?? 50;
            if ($ms > 5000) // we don't want it to be more than 5 sec
            {
                $ms = 5000;
            }
            // Simulating I/O delay (DB/file read) with timer
            Loop::addTimer($ms / 1000 + (rand(1, 20) / 100), function () use ($resolve, $request) {
                $postId = $request->getAttribute('params')['id'] ?? 0;
                $post = new PostModel();
                $post->Id = $postId;
                $post->Name = "Viewi ft. ReactPHP $postId";
                $post->Version = $postId + 1000;
                $response = new RawJsonResponse($post);
                // echo "request Loop:1. \n";
                $resolve($response);
            });
        });
    }
}
```

## Async Interceptors

[http://localhost:8080/interceptors-test/100500](http://localhost:8080/interceptors-test/100500)

This page demonstrates async interceptors during SSR

![Alt text](screenshots/asyncInterceptors.png?raw=true "Async Interceptors")

```php
<?php

namespace Components\Views\InterceptorsTest;

use Components\Models\PostModel;
use Components\Services\Interceptors\AuthorizationInterceptor;
use Components\Services\Interceptors\SessionInterceptor;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;

class InterceptorsTestComponent extends BaseComponent
{
    public string $title = 'SSR + Interceptors with Deferred (async) response';
    public ?PostModel $post = null;
    public string $message = '';

    public function __construct(private int $id, private HttpClient $http)
    {
    }

    public function init()
    {
        $this->http
            ->withInterceptor(SessionInterceptor::class)
            ->withInterceptor(AuthorizationInterceptor::class)
            ->get("/api/posts/{$this->id}/async/200")->then(
                function (PostModel $post) {
                    $this->post = $post;
                },
                function ($error) {
                    $this->message = $error;
                }
            );
    }
}
```

## Async Middleware (Viewi IMiddleware)

[http://localhost:8080/middleware-test/100500](http://localhost:8080/middleware-test/100500)

This page demonstrates using guards in asynchronous mode

![Alt text](screenshots/asyncMiddleware.png?raw=true "Async middleware")

```php
<?php

namespace Components\Views\MiddlewareTest;

use Components\Models\PostModel;
use Components\Services\Middleware\AuthGuard;
use Components\Services\Middleware\SessionGuard;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;

#[Middleware([AuthGuard::class, SessionGuard::class])]
class MiddlewareTestComponent extends BaseComponent
{
    public string $title = 'SSR + Guards with Deferred (async) response';
    public ?PostModel $post = null;
    public string $message = '';

    public function __construct(private int $id, private HttpClient $http)
    {
    }

    public function init()
    {
        $this->http
            ->get("/api/posts/{$this->id}/async/1")->then(
                function (PostModel $post) {
                    $this->post = $post;
                },
                function ($error) {
                    $this->message = $error;
                }
            );
    }
}
```

## Middleware Unauthorized example

[http://localhost:8080/middleware-fail-test/100500](http://localhost:8080/middleware-fail-test/100500)

This page should redirect you back to home page. Guard will check authorization and it won't pass.

## Counter

[http://localhost:8080/counter](http://localhost:8080/counter)

and

[http://localhost:8080/counter/123](http://localhost:8080/counter/123)

Demo of a handling click events in order to update the viewi with a new count value.

![Alt text](screenshots/counter.png?raw=true "Counter")

```html
<button (click)="decrement()" class="mui-btn mui-btn--accent">-</button>
<span class="mui--text-dark mui--text-title">$count</span>
<button (click)="increment()" class="mui-btn mui-btn--accent">+</button>
```

Also it demonstrates how to inject route parameter into the component:

`$router->get('/counter/{page}', CounterPage::class);`

```php
<?php

namespace Components\Views\Pages;

use Viewi\Components\BaseComponent;

class CounterPage extends BaseComponent
{
    public int $page;
    
    public function __construct(int $page = 0)
    {
        $this->page = $page;
    }
}
```

## Todo

[http://localhost:8080/todo](http://localhost:8080/todo)

Demonstrates a small Todo application.

![Alt text](screenshots/todo.png?raw=true "Todo")

## Redirect test

[http://localhost:8080/redirect-test](http://localhost:8080/redirect-test)

Demonstrates how to redirect to another page using `ClientRouter`

```php
<?php

namespace Components\Views\Pages;

use Viewi\Components\BaseComponent;
use Viewi\Components\Routing\ClientRoute;

class RedirectTestComponent extends BaseComponent
{
    public function __construct(private ClientRoute $router)
    {
    }

    public function init()
    {
        $this->router->navigate('/');
    }
}
```

## Curren Url page

[http://localhost:8080/current-url](http://localhost:8080/current-url)

This page demonstrates how to get a current url path of the page with `ClientRouter`

```php
<?php

namespace Components\Views\Pages;

use Viewi\Components\BaseComponent;
use Viewi\Components\Routing\ClientRoute;

class CurrentUrlTestPage extends BaseComponent
{
    public ?string $currentUrl = null;

    public function __construct(ClientRoute $router)
    {
        $this->currentUrl = $router->getUrl();
    }
}
```

