<?php

namespace Components\Views\MiddlewareTest;

use Components\Models\PostModel;
use Components\Services\Middleware\AuthGuardFail;
use Components\Services\Middleware\SessionGuard;
use Viewi\BaseComponent;
use Viewi\Common\HttpClient;

class MiddlewareFailTestComponent extends BaseComponent
{
    public static array $_beforeStart = [AuthGuardFail::class, SessionGuard::class];

    public string $title = 'SSR + Guards with Deferred (async) response';
    public ?PostModel $post = null;
    public string $message = '';

    public function __init(int $id, HttpClient $http)
    {
        $http
            ->get("/api/posts/$id/async/1")->then(
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
