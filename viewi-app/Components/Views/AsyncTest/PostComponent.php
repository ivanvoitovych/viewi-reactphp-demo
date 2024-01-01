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
