<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;

/**
 * Indicates wrong snippet type.
 */
class WrongSnippetTypeException extends \Exception
{
    /**
     * @param string $expected
     * @param string $actual
     */
    public function __construct(private $actual, private $expected, private SnippetDocument $document)
    {
        parent::__construct(
            \sprintf('Wrong snippet type were detected (actual: "%s", expected: "%s").', $actual, $expected)
        );
    }

    /**
     * @return string
     */
    public function getActual()
    {
        return $this->actual;
    }

    /**
     * @return string
     */
    public function getExpected()
    {
        return $this->expected;
    }

    /**
     * @return SnippetDocument
     */
    public function getDocument()
    {
        return $this->document;
    }
}
