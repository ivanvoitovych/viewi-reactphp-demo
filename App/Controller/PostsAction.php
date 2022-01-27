<?php

namespace App\Controller;

use App\Message\RawJsonResponse;
use Components\Models\PostModel;
use Psr\Http\Message\ServerRequestInterface;

class PostsAction
{
    public function __invoke(ServerRequestInterface $request) {
        $post = new PostModel();
        $post->Id = $request->getAttribute('params')['id'] ?? 0;
        $post->Name = 'Viewi ft. ReactPHP';
        $post->Version = 1;
        return new RawJsonResponse($post);
    }
}