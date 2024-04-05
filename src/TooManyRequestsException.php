<?php
/**
 * Copyright 2024 Treehill Studio (https://treehillstudio.com)
 * Use of this source code is governed by an MIT
 * license that can be found in the LICENSE file.
 */

namespace Reverso;

/**
 * Exception thrown when too many requests are made to the Reverso API too quickly.
 */
class TooManyRequestsException extends ReversoException
{
}
