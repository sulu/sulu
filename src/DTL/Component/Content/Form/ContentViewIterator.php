<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Form;

use Symfony\Component\Form\Exception\BadMethodCallException;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ContentViewIterator implements \ArrayAccess, \Iterator, \Countable
{
    private $viewResolver;
    private $documents;
    private $index = 0;

    public function __construct(ContentViewResolver $viewResolver, $documents)
    {
        $this->viewResolver = $viewResolver;
        $this->documents = $documents;
    }

    public function rewind()
    {
        reset($this->documents);
    }

    public function current()
    {
        return $this->resolveView(current($this->documents));
    }

    public function next()
    {
        next($this->documents);
    }

    public function key()
    {
        return key($this->documents);
    }

    public function valid()
    {
        return current($this->documents);
    }

    /**
     * Implements \Countable.
     *
     * @return int The number of children views
     */
    public function count()
    {
        return count($this->children);
    }
}
