<?php
/**
 * Copyright 2024 Treehill Studio (https://treehillstudio.com)
 * Use of this source code is governed by an MIT
 * license that can be found in the LICENSE file.
 */

namespace Reverso;

/**
 * Exception thrown when attempting to download a document that is not ready for download.
 * @see Translator::downloadDocument()
 */
class DocumentNotReadyException extends ReversoException
{
}
