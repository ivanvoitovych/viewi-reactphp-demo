<?php

namespace Components\Views\AsyncTest;

use Components\Models\PostModel;
use Viewi\BaseComponent;
use Viewi\Common\HttpClient;

class AsyncTestComponent extends BaseComponent
{
    public string $title = 'Server side rendering with Deferred (async) response';
    public ?PostModel $post = null;

    public function __init(int $id, HttpClient $http)
    {
        $http->get("/api/posts/$id/async")->then(
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
