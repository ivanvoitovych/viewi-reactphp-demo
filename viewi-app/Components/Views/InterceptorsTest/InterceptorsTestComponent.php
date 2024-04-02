<?php

namespace Components\Views\InterceptorsTest;

use Components\Models\PostModel;
use Components\Services\Interceptors\AuthorizationInterceptor;
use Components\Services\Interceptors\SessionInterceptor;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;

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
                function (Response $response) {
                    $this->message = $response->body;
                }
            );
    }
}
