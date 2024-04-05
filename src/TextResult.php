<?php
/**
 * Copyright 2024 Treehill Studio (https://treehillstudio.com)
 * Use of this source code is governed by an MIT
 * license that can be found in the LICENSE file.
 */

namespace Reverso;

/**
 * Holds the result of a text translation request.
 */
class TextResult
{
    /**
     * @var string String containing the translated text.
     */
    public $text;

    /**
     * @var bool True if the translation is truncated.
     */
    public $truncated;

    /**
     * @var int Number of the not translated words.
     */
    public $wordsLeft;

    public function __construct(string $text, bool $truncated, int $wordsLeft)
    {
        $this->text = $text;
        $this->truncated = $truncated;
        $this->wordsLeft = $wordsLeft;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
