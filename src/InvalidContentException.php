<?php
/**
 * Copyright 2024 Treehill Studio (https://treehillstudio.com)
 * Use of this source code is governed by an MIT
 * license that can be found in the LICENSE file.
 */

namespace Reverso;

use JsonException;

class InvalidContentException extends ReversoException
{
    public function __construct(JsonException $exception)
    {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }
}
