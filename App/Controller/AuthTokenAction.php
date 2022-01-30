<?php

namespace App\Controller;

use App\Message\RawJsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\Message\Response;
use React\Promise\Promise;

class AuthTokenAction
{
    public function __invoke(ServerRequestInterface $request)
    {
        return new Promise(function ($resolve, $reject) use ($request) {
            // Simulating I/O delay (DB/file read) with timer
            Loop::addTimer(0.2, function () use ($resolve, $request) {
                $valid = $request->getAttribute('params')['valid'] === 'true';
                if (!$valid) {
                    $resolve(new Response(401, [], ''));
                    return;
                }
                $response = new RawJsonResponse(['token' => 'base64string']);
                $resolve($response);
            });
        });
    }
}
