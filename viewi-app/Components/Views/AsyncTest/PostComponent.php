<?php

namespace Components\Views\AsyncTest;

use Components\Models\PostModel;
use Viewi\BaseComponent;
use Viewi\Common\HttpClient;

class PostComponent extends BaseComponent
{
    public ?int $id = 0;
    public ?PostModel $post = null;
    private  HttpClient $http;

    public function __init(HttpClient $http)
    {
        $this->http = $http;
    }

    public function __mounted()
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
