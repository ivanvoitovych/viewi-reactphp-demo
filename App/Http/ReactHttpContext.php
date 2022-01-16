<?php

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;
use Viewi\WebComponents\IHttpContext;

class ReactHttpContext implements IHttpContext
{
    private array $responseHeaders = [];
    private ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getResponseHeaders(): ?array
    {
        return $this->responseHeaders;
    }

    public function setResponseHeader(string $key, string $value): void
    {
        $this->responseHeaders[$key] = $value;
    }

    public function getCurrentUrl(): ?string
    {
        return $this->request->getUri()->getPath();
    }
}