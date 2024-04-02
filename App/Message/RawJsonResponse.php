<?php

namespace App\Message;

use React\Http\Message\Response;

class RawJsonResponse extends Response
{
    private $data = null;
    /**
     * @param int                                            $status  HTTP status code (e.g. 200/404)
     * @param array<string,string|string[]>                  $headers additional response headers
     * @param string|ReadableStreamInterface|StreamInterface $body    response body
     * @param string                                         $version HTTP protocol version (e.g. 1.1/1.0)
     * @param ?string                                        $reason  custom HTTP response phrase
     * @throws \InvalidArgumentException for an invalid body
     */
    public function __construct(
        $data,
        $status = 200,
        array $headers = array(),
        $version = '1.1',
        $reason = null
    ) {
        $this->data = $data;
        $body = json_encode($data) . "\n";
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        parent::__construct(
            $status,
            $headers,
            $body,
            $version,
            $reason
        );
    }

    public function getData()
    {
        return $this->data;
    }
}