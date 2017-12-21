<?php

namespace Aerys;

interface Response {
    public function getHeaders(): array;
    public function getBody(): \Amp\ByteStream\InputStream;
}
