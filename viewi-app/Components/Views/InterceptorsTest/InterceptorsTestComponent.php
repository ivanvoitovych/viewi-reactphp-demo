<?php

namespace Components\Views\InterceptorsTest;

use Components\Models\PostModel;
use Components\Services\Interceptors\AuthorizationInterceptor;
use Components\Services\Interceptors\SessionInterceptor;
use Viewi\BaseComponent;
use Viewi\Common\HttpClient;

class InterceptorsTestComponent extends BaseComponent
{
    public string $title = 'SSR + Interceptors with Deferred (async) response';
    public ?PostModel $post = null;
    public string $message = '';

    public function __init(int $id, HttpClient $http, SessionInterceptor $session, AuthorizationInterceptor $auth)
    {
        $http
            ->with([$session, 'intercept'])
            ->with([$auth, 'intercept'])
            ->get("/api/posts/$id/async/200")->then(
                function (PostModel $post) {
                    $this->post = $post;
                    // print_r("http->get->then->RESOLVED\n");
                },
                function ($error) {
                    $this->message = $error;
                    // print_r("http->get->then->ERROR\n");
                }
            );
    }
}
