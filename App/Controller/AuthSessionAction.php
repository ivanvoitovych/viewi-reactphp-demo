<?php

namespace App\Controller;

use App\Message\RawJsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Promise\Promise;

class AuthSessionAction
{
    public function __invoke(ServerRequestInterface $request)
    {
        return new Promise(function ($resolve, $reject) {
            // Simulating I/O delay (DB/file read) with timer
            Loop::addTimer(0.2, function () use ($resolve) {
                $response = new RawJsonResponse(['session' => '000-1111-2222']);
                $resolve($response);
            });
        });
    }
}
