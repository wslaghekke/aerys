<?php

namespace Aerys;

use Amp\ByteStream\IteratorStream;
use Amp\ByteStream\Message;

abstract class StandardRequest implements Request {
    protected $method;
    protected $uri;
    protected $protocol;
    protected $headers;
    protected $maxBodySize;
    protected $client;
    protected $body;
    protected $streamId;
    protected $locals;
    
    private $queryParams;
    private $currentBody;

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): string {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion(): string {
        return $this->protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $field) {
        return $this->headers[strtolower($field)][0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderArray(string $field): array {
        return $this->headers[strtolower($field)] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllHeaders(): array {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(int $bodySize = -1): Message {
        if ($bodySize > -1) {
            if ($bodySize > ($this->maxBodySize ?? $this->client->options->maxBodySize)) {
                $this->maxBodySize = $bodySize;
                $this->client->httpDriver->upgradeBodySize($this->internalRequest);
            }
        }

        if ($this->body != $this->currentBody) {
            $this->currentBody = $this->body;
            $this->body->onResolve(function ($e, $data) {
                if ($e instanceof ClientSizeException) {
                    $bodyEmitter = $this->client->bodyEmitters[$this->streamId];
                    $this->body = new Message(new IteratorStream($bodyEmitter->iterate()));
                    $bodyEmitter->emit($data);
                }
            });
        }
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function getParam(string $name) {
        return ($this->queryParams ?? $this->queryParams = $this->parseParams())[$name][0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getParamArray(string $name): array {
        return ($this->queryParams ?? $this->queryParams = $this->parseParams())[$name] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllParams(): array {
        return $this->queryParams ?? $this->queryParams = $this->parseParams();
    }

    private function parseParams() {
        if (empty($this->uriQuery)) {
            return $this->queryParams = [];
        }

        $pairs = explode("&", $this->uriQuery);
        if (count($pairs) > $this->client->options->maxInputVars) {
            throw new ClientSizeException;
        }

        $this->queryParams = [];
        foreach ($pairs as $pair) {
            $pair = explode("=", $pair, 2);
            // maxFieldLen should not be important here ... if it ever is, create an issue...
            $this->queryParams[urldecode($pair[0])][] = urldecode($pair[1] ?? "");
        }

        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie(string $name) {
        return $this->cookies[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalVar(string $key) {
        return $this->locals[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocalVar(string $key, $value) {
        $this->locals[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionInfo(): array {
        $client = $this->client;
        return [
            "client_port" => $client->clientPort,
            "client_addr" => $client->clientAddr,
            "server_port" => $client->serverPort,
            "server_addr" => $client->serverAddr,
            "is_encrypted"=> $client->isEncrypted,
            "crypto_info" => $client->cryptoInfo,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getOption(string $option) {
        return $this->client->options->{$option};
    }
}
