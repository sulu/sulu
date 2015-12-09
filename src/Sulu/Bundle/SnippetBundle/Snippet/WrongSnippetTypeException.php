<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;

class WrongSnippetTypeException extends \Exception
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var SnippetDocument
     */
    private $document;

    public function __construct($key, SnippetDocument $document)
    {
        $this->key = $key;
        $this->document = $document;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return SnippetDocument
     */
    public function getDocument()
    {
        return $this->document;
    }
}
