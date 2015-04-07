<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Query;

use PHPCR\Query\QueryResultInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;

/**
 * Lazily hydrate query results
 */
class ResultCollection implements \Iterator, \Countable
{
    private $eventDispatcher;
    private $result;
    private $locale;

    private $initialized = false;
    private $rows;
    private $primarySelector = null;

    public function __construct(QueryResultInterface $result, EventDispatcherInterface $eventDispatcher, $locale, $primarySelector = null)
    {
        $this->result = $result;
        $this->eventDispatcher = $eventDispatcher;
        $this->primarySelector = $primarySelector;
        $this->locale = $locale;
    }

    public function count()
    {
        $this->initialize();
        return $this->rows->count();
    }

    public function current()
    {
        $this->initialize();
        $row = $this->rows->current();
        $node = $row->getNode($this->primarySelector);

        $hydrateEvent = new HydrateEvent($node, $this->locale);
        $this->eventDispatcher->dispatch(Events::HYDRATE, $hydrateEvent);

        return $hydrateEvent->getDocument();
    }

    public function key()
    {
        $this->initialize();

        return $this->rows->key();
    }

    public function next()
    {
        $this->initialize();

        return $this->rows->next();
    }

    public function rewind()
    {
        $this->initialize();

        return $this->rows->rewind();
    }

    public function valid()
    {
        $this->initialize();

        return $this->rows->valid();
    }

    public function getArrayCopy()
    {
        $copy = array();
        foreach ($this as $document) {
            $copy[] = $document;
        }

        return $copy;
    }

    private function initialize()
    {
        if (true === $this->initialized) {
            return;
        }

        $this->rows = $this->result->getRows();
        $this->initialized = true;
    }
}
