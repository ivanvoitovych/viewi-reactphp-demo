<?php

namespace App\Controller;

use App\Message\RawJsonResponse;
use Components\Models\PostModel;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Promise\Promise;

class PostsActionAsync
{
    public function __invoke(ServerRequestInterface $request)
    {
        return new Promise(function ($resolve, $reject) use ($request) {
            $ms = $request->getAttribute('params')['ms'] ?? 1000;
            if ($ms > 5000) // we don't want it to be more than 5 sec
            {
                $ms = 5000;
            }
            Loop::addTimer($ms / 1000 + (rand(1, 20) / 100), function () use ($resolve, $request) {
                $postId = $request->getAttribute('params')['id'] ?? 0;
                $post = new PostModel();
                $post->Id = $postId;
                $post->Name = "Viewi ft. ReactPHP $postId";
                $post->Version = $postId + 1000;
                $response = new RawJsonResponse($post);
                // echo "request Loop:1. \n";
                $resolve($response);
            });
        });
    }
}
