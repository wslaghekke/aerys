<?php

// TODO: move to amphp/byte-stream

namespace Aerys;

use Amp\ByteStream\InputStream;
use Amp\Promise;
use Amp\Success;

class PrefixStream implements InputStream {
    private $prefixes;
    private $stream;

    public function __construct($prefixes, InputStream $stream) {
        $this->prefixes = array_reverse($prefixes);
        $this->stream = $stream;
    }

    public function read(): Promise {
        if (null !== $prefix = array_pop($this->prefixes)) {
            return new Success($prefix);
        }
        return $this->stream->read();
    }
}