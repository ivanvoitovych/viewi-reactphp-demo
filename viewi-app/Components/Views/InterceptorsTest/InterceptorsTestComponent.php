<?php

namespace Components\Views\InterceptorsTest;

use Components\Models\PostModel;
use Components\Services\Interceptors\SessionInterceptor;
use Viewi\BaseComponent;
use Viewi\Common\HttpClient;

class InterceptorsTestComponent extends BaseComponent
{
    public string $title = 'SSR + Interceptors with Deferred (async) response';
    public ?PostModel $post = null;

    public function __init(int $id, HttpClient $http, SessionInterceptor $session)
    {
        $http
            ->with([$session, 'intercept'])
            ->get("/api/posts/$id/async")->then(
                function (PostModel $post) {
                    $this->post = $post;
                    // print_r("http->get->then->RESOLVED\n");
                },
                function ($error) {
                    // print_r("http->get->then->ERROR\n");
                }
            );
    }
}
