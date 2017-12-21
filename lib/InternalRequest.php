<?php

namespace Aerys;

use Amp\Coroutine;
use Amp\Promise;
use Amp\Struct;
use Amp\Success;

class InternalRequest extends StandardRequest {
    use Struct;

    /** @var Client */
    public $client;
    /** @var \Generator */
    public $responseWriter;
    /** @var array */
    public $badFilterKeys = [];
    /** @var boolean */
    public $filterErrorFlag;
    /** @var integer */
    public $streamId = 0;
    /** @var string|array literal trace for HTTP/1, for HTTP/2 an array of [name, value] arrays in the original order */
    public $trace;
    /** @var string */
    public $protocol;
    /** @var string */
    public $method;
    /** @var array */
    public $headers;
    /** @var \Amp\ByteStream\Message */
    public $body;
    /** @var int */
    public $maxBodySize;
    /** @var string */
    public $uri;
    /** @var string */
    public $uriScheme;
    /** @var string */
    public $uriHost;
    /** @var integer */
    public $uriPort;
    /** @var string */
    public $uriPath;
    /** @var string */
    public $uriQuery;
    /** @var array */
    public $cookies;
    /** @var int */
    public $time;
    /** @var string */
    public $httpDate;
    /** @var array */
    public $locals = [];

    public $middlewares = [];
    public $middlewareIndex = 0;
    public $responder;

    public function submit(...$args): Promise {
        $response = ($this->middlewares[$this->middlewareIndex++] ?? $this->responder)($this, ...$args);
        if ($response instanceof \Generator) {
            return new Coroutine($response);
        }
        if ($response instanceof Promise) {
            return $response;
        }
        \assert($response instanceof Response);
        return new Success($response);
    }
}
