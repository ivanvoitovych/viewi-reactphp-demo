<?php

namespace Components\Views\AsyncTest;

use Components\Models\PostModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;

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
            function (Response $response) {
                echo $response->body;
                // print_r(['$http->get->then->error', $error]);
            }
        );
    }
}
