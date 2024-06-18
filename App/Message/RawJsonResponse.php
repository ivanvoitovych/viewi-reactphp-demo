<?php

namespace App\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use React\Http\Message\Response;

class RawJsonResponse implements ResponseInterface
{
    private mixed $data = null;
    private Response $response;

    public function __construct($data, $status = Response::STATUS_OK)
    {
        $this->data = $data;
        $this->response = (Response::json($data))->withStatus($status);
    }

    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    public function withStatus(int $code, string $reasonPhrase = '')
    {
        $this->response = $this->response->withStatus($code, $reasonPhrase);
        return $this;
    }

    public function getReasonPhrase()
    {
        return $this->response->getReasonPhrase();
    }

    public function getProtocolVersion()
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion(string $version)
    {
        $this->response = $this->response->withProtocolVersion($version);
        return $this;
    }

    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name)
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader(string $name)
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name)
    {
        return $this->response->getHeaderLine($name);
    }

    public function withHeader(string $name, $value)
    {
        $this->response = $this->response->withHeader($name, $value);
        return $this;
    }

    public function withAddedHeader(string $name, $value)
    {
        $this->response = $this->response->withAddedHeader($name, $value);
        return $this;
    }

    public function withoutHeader(string $name)
    {
        $this->response = $this->response->withoutHeader($name);
        return $this;
    }

    public function getBody()
    {
        return $this->response->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        $this->response = $this->response->withBody($body);
        return $this;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
