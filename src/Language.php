<?php
/**
 * Copyright 2024 Treehill Studio (https://treehillstudio.com)
 * Use of this source code is governed by an MIT
 * license that can be found in the LICENSE file.
 */

namespace Reverso;

/**
 * Information about a language supported by Reverso translator.
 */
class Language
{
    /**
     * @var string Name of the language in English.
     */
    public $name;

    /**
     * @var string Language code according to ISO 639-1, for example 'en'. Some target languages also include the
     * regional variant according to ISO 3166-1, for example 'en-US'.
     */
    public $code;

    /**
     * @var string Language code according to ISO 639-3, for example 'eng'.
     */
    public $long;

    public function __construct(string $name, string $code, string $long)
    {
        $this->name = $name;
        $this->code = $code;
        $this->long = $long;
    }

    public static function __set_state(array $array)
    {
        $object = new Language($array['name'], $array['code'], $array['long']);
        return $object;
    }

    public function __toString(): string
    {
        return "$this->name ($this->code)";
    }
}
