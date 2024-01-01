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
