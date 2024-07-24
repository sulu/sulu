<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Collection;

use PHPCR\Query\QueryResultInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Lazily hydrate query results.
 */
class QueryResultCollection extends AbstractLazyCollection
{
    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @param string $locale
     * @param array $options
     * @param null|string $primarySelector
     */
    public function __construct(
        private QueryResultInterface $result,
        private EventDispatcherInterface $eventDispatcher,
        private $locale,
        private $options = [],
        private $primarySelector = null
    ) {
    }

    public function current()
    {
        $this->initialize();
        $row = $this->documents->current();
        $node = $row->getNode($this->primarySelector);

        $hydrateEvent = new HydrateEvent($node, $this->locale, $this->options);
        $this->eventDispatcher->dispatch($hydrateEvent, Events::HYDRATE);

        return $hydrateEvent->getDocument();
    }

    protected function initialize()
    {
        if (true === $this->initialized) {
            return;
        }

        $this->documents = $this->result->getRows();
        $this->initialized = true;
    }
}
