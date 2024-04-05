<?php
/**
 * Copyright 2024 Treehill Studio (https://treehillstudio.com)
 * Use of this source code is governed by an MIT
 * license that can be found in the LICENSE file.
 */

namespace Reverso;

/**
 * Exception thrown when a connection error occurs while accessing the Reverso API.
 */
class ConnectionException extends ReversoException
{
    /**
     * @var bool True if this connection error is due to a transient condition and the request should be retried, false
     * otherwise.
     */
    public $shouldRetry;

    public function __construct(string $message, int $code, $previous, bool $shouldRetry)
    {
        parent::__construct($message, $code, $previous);
        $this->shouldRetry = $shouldRetry;
    }
}
