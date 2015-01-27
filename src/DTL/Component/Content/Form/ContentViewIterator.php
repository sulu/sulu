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
 * The content view iterator is used to iterate over a collection of views.
 *
 * An example of this is given by the smart content content type which aggregates
 * a number of structure documents and allows the user to iterate over them.
 *
 * By creating a content view iterator the developer will ensure that views are
 * lazy-loaded.
 */
class ContentViewIterator implements \Iterator, \Countable
{
    /**
     * @var ContentViewResolver
     */
    private $viewResolver;

    /**
     * @var FormDocument[]
     */
    private $documents;

    /**
     * @param ContentViewResolver $viewResolver
     * @param FormDocument[] $documents
     */
    public function __construct(ContentViewResolver $viewResolver, $documents)
    {
        $this->viewResolver = $viewResolver;
        $this->documents = new \ArrayIterator($documents);
    }

    public function rewind()
    {
        $this->documents->rewind();
    }

    public function current()
    {
        return $this->viewResolver->resolve($this->documents->current());
    }

    public function next()
    {
        $this->documents->next();
    }

    public function key()
    {
        return $this->documents->key();
    }

    public function valid()
    {
        return $this->documents->valid();
    }

    /**
     * Implements \Countable.
     *
     * @return int The number of children views
     */
    public function count()
    {
        return $this->documents->count();
    }
}
