<?php

namespace Components\Views\MiddlewareTest;

use Components\Models\PostModel;
use Components\Services\Middleware\AuthGuardFail;
use Components\Services\Middleware\SessionGuard;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;

#[Middleware([AuthGuardFail::class, SessionGuard::class])]
class MiddlewareFailTestComponent extends BaseComponent
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
                function (Response $response) {
                    $this->message = $response->body;
                }
            );
    }
}
